<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;

class ComposerRequireGenerator extends BaseGenerator
{
    public function handle()
    {
        $requires = $this->collectRequires();

        if (empty($requires)) {
            return;
        }

        $requires = $this->maybeInstallStarPackages($requires);

        $composerPath = base_path('composer.json');

        if (!file_exists($composerPath)) {
            $this->logger()->error("composer.json not found at {$composerPath}");
            return;
        }

        // Load composer.json
        $composerContent = file_get_contents($composerPath);
        $composer = json_decode($composerContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger()->error("Failed to parse composer.json: " . json_last_error_msg());
            return;
        }

        // Ensure require section exists
        if (!isset($composer['require'])) {
            $composer['require'] = [];
        }

        $existingPackages = [];
        $newPackages = [];

        // Check which packages already exist
        foreach ($requires as $package => $version) {
            if (isset($composer['require'][$package])) {
                $existingPackages[$package] = [
                    'old' => $composer['require'][$package],
                    'new' => $version,
                ];
            } else {
                $newPackages[$package] = $version;
            }
        }

        // Ask for confirmation on existing packages
        $packagesToUpdate = [];
        foreach ($existingPackages as $package => $versions) {
            if ($versions['old'] === $versions['new']) {
                $this->logger()->info("Package {$package} already exists with version {$versions['old']}");
                continue;
            }

            $override = $this->logger()->confirm(
                "Package {$package} already exists (current: {$versions['old']}, new: {$versions['new']}). Override?",
                true
            );

            if ($override) {
                $packagesToUpdate[$package] = $versions['new'];
            }
        }

        // Merge new packages and packages to update
        $allPackagesToAdd = array_merge($newPackages, $packagesToUpdate);

        if (empty($allPackagesToAdd)) {
            $this->logger()->info("No changes to composer.json");
            return;
        }

        // Add/update packages
        foreach ($allPackagesToAdd as $package => $version) {
            $composer['require'][$package] = $version;
            $action = isset($existingPackages[$package]) ? 'Updated' : 'Added';
            $this->logger()->info("{$action} {$package}: {$version}");
        }

        // Sort require section alphabetically
        ksort($composer['require']);

        // Save composer.json with pretty print
        $updatedContent = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($composerPath, $updatedContent);

        $this->logger()->info("composer.json updated successfully");
        $this->logger()->info("Run 'composer update' to install the new packages");
    }

    /**
     * Collect required packages from all three sources (app-level, per-resource,
     * generator-pushed) and resolve each to a single version using precedence
     * and '*' deferral rules.
     */
    protected function collectRequires(): array
    {
        $bySource = $this->collectFromSources();

        if (empty($bySource)) {
            return [];
        }

        $resolved = [];
        foreach ($bySource as $package => $sources) {
            $resolved[$package] = $this->resolveVersion($package, $sources);
        }

        return $resolved;
    }

    /**
     * @return array<string, array<string, string>>  package => source => version
     */
    protected function collectFromSources(): array
    {
        $bySource = [];

        // Source 1 — app-level requires from input.require (or resource.require as fallback
        // for single-resource invocations without an input wrapper).
        $input = $this->input();
        $resource = $this->resource();

        $appRequires = [];
        if ($input && $input->has('require')) {
            $appRequires = $input->get('require', []);
        } elseif ($resource->has('require')) {
            $appRequires = $resource->get('require', []);
        }
        foreach ((array) $appRequires as $package => $version) {
            $bySource[$package]['app'] = (string) $version;
        }

        // Source 2 — per-resource requires from input.resources[].require.
        $resources = [];
        if ($input && $input->has('resources')) {
            $resources = $input->get('resources', []);
        }
        foreach ((array) $resources as $r) {
            if (!is_array($r) || empty($r['require']) || !is_array($r['require'])) {
                continue;
            }

            foreach ($r['require'] as $package => $version) {
                $version = (string) $version;
                $existing = $bySource[$package]['resource'] ?? null;

                if ($existing === null) {
                    $bySource[$package]['resource'] = $version;
                    continue;
                }

                if ($existing === $version) {
                    continue;
                }

                // Prefer a concrete version over '*'.
                if ($existing === '*') {
                    $bySource[$package]['resource'] = $version;
                    continue;
                }
                if ($version === '*') {
                    continue;
                }

                // Two different concrete versions across resources — keep the first.
                $this->logger()->warn("Conflicting resource-level requires for {$package}: '{$existing}' vs '{$version}' — keeping '{$existing}'");
            }
        }

        // Source 3 — generator-pushed requires via $this->requirePackage(...).
        $generatorRequires = $this->context->get('composer_requires', []);
        if (is_array($generatorRequires)) {
            foreach ($generatorRequires as $package => $version) {
                $bySource[$package]['generator'] = (string) $version;
            }
        }

        return $bySource;
    }

    /**
     * Resolve the version for a package using the precedence app > resource > generator.
     * A '*' value at any source is treated as "defer to another source." If every source
     * is '*' (or only '*' was declared), '*' is returned and a warning is logged.
     */
    protected function resolveVersion(string $package, array $bySource): string
    {
        foreach (['app', 'resource', 'generator'] as $source) {
            $version = $bySource[$source] ?? null;
            if ($version !== null && $version !== '*') {
                return $version;
            }
        }

        $this->logger()->warn("No concrete version found for {$package}; falling back to '*'. Pin a version in `require` to silence this.");
        return '*';
    }

    /**
     * For each package resolved to '*', optionally shell out to `composer require`
     * so Composer can pick a concrete version. Falls back to keeping '*' if Composer
     * isn't available, the user declines, or the install errors out.
     */
    protected function maybeInstallStarPackages(array $resolved): array
    {
        $stars = array_keys(array_filter($resolved, fn($v) => $v === '*'));

        if (empty($stars)) {
            return $resolved;
        }

        if (!$this->isComposerAvailable()) {
            $this->logger()->info("Composer not available — keeping '*' for unpinned packages.");
            return $resolved;
        }

        foreach ($stars as $package) {
            if (!$this->confirmInstall($package)) {
                continue;
            }

            try {
                $this->runComposerRequire($package);
                $installed = $this->readInstalledVersion($package);
                if ($installed !== null && $installed !== '*') {
                    $resolved[$package] = $installed;
                    $this->logger()->info("Installed {$package} at {$installed}");
                } else {
                    $this->logger()->warn("composer require {$package} succeeded but no version was recorded in composer.json — keeping '*'.");
                }
            } catch (\Throwable $e) {
                $this->logger()->warn("composer require {$package} failed: " . $e->getMessage() . " — keeping '*'.");
            }
        }

        return $resolved;
    }

    /**
     * Probe whether composer is callable in this environment.
     *
     * Returns false if proc_open is disabled, Symfony Process is missing, or `composer --version`
     * fails for any reason. Overridable in tests.
     */
    protected function isComposerAvailable(): bool
    {
        if (!class_exists(\Symfony\Component\Process\Process::class)) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('proc_open', $disabled, true)) {
            return false;
        }

        try {
            $process = new \Symfony\Component\Process\Process(['composer', '--version']);
            $process->setTimeout(10);
            $process->run();
            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Ask the user whether to install $package via `composer require`. Default yes.
     * Skips (returns false) if the logger doesn't support interactive confirmation.
     */
    protected function confirmInstall(string $package): bool
    {
        $logger = $this->logger();
        if (!method_exists($logger, 'confirm')) {
            return false;
        }
        return (bool) $logger->confirm("Install '{$package}' via 'composer require'?", true);
    }

    /**
     * Run `composer require <package>` non-interactively in the project root.
     * Throws on non-zero exit. Overridable in tests.
     */
    protected function runComposerRequire(string $package): void
    {
        $process = new \Symfony\Component\Process\Process(
            ['composer', 'require', '--no-interaction', $package],
            base_path()
        );
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            $stderr = trim($process->getErrorOutput());
            throw new \RuntimeException($stderr !== '' ? $stderr : 'composer require exited non-zero');
        }
    }

    /**
     * Read the version Composer recorded for $package in composer.json after install.
     * Overridable in tests.
     */
    protected function readInstalledVersion(string $package): ?string
    {
        $path = base_path('composer.json');
        if (!file_exists($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);
        return $json['require'][$package] ?? null;
    }
}
