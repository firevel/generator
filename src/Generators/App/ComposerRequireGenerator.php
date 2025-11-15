<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;

class ComposerRequireGenerator extends BaseGenerator
{
    public function handle()
    {
        $resource = $this->resource();

        // Get the full input to access top-level "require" field
        $input = $this->input();

        // Check if require field exists, if not skip silently
        if ($input && $input->has('require')) {
            $requires = $input->get('require', []);
        } elseif ($resource->has('require')) {
            $requires = $resource->get('require', []);
        } else {
            // No require field - skip silently since it's optional
            return;
        }

        if (empty($requires)) {
            return;
        }

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
}
