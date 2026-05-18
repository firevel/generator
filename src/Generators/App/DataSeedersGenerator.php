<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;
use Illuminate\Support\Str;

/**
 * Iterates the `seeders` block (a map of <set-name> => Entry[]) and
 * writes one `<Studly(set)>DataSeeder.php` per non-empty set.
 *
 * Set names are arbitrary user-supplied namespaces — the generator
 * has no special knowledge of `system`, `demo`, or any other name.
 *
 * Each entry is shape `{ ClassFQN: { col: Value, ... } }` and is
 * rendered as `\Class::insert([ 'col' => Value, ... ])`. The Value
 * grammar (scalar / list of scalars / single-level invocation) is
 * documented in the data-seeder Blade stub.
 */
class DataSeedersGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates one <Set>DataSeeder.php per non-empty set under `seeders.*`.';
    }

    public function handle()
    {
        $sets = $this->normalizeSets($this->resource()->all());

        if ($sets === []) {
            return;
        }

        $emitted = [];

        foreach ($sets as $name => $entries) {
            if ($entries === []) {
                continue;
            }

            $className = Str::studly($name) . 'DataSeeder';

            $source = $this->render('app/data-seeder', [
                'className' => $className,
                'entries' => $entries,
            ]);

            $this->createFile(
                database_path('seeders') . '/' . $className . '.php',
                $source
            );

            $emitted[] = $className;
            $this->logger()?->info("  {$className}: " . count($entries) . ' row(s)');
        }

        // Hand the ordered list of emitted classes to DatabaseSeederGenerator.
        $this->context->set('seeders.emitted_classes', $emitted);
    }

    /**
     * @param array<mixed> $raw
     * @return array<string, array<int, mixed>>
     */
    private function normalizeSets(array $raw): array
    {
        $out = [];

        foreach ($raw as $name => $entries) {
            if (! is_string($name) || $name === '') {
                throw new \InvalidArgumentException(
                    'seeders keys must be non-empty strings naming a set (e.g. "system", "demo").'
                );
            }

            if ($entries === null) {
                continue;
            }

            if (! is_array($entries)) {
                throw new \InvalidArgumentException(
                    "seeders.{$name} must be an array of {ClassFQN: {col: value}} entries."
                );
            }

            $out[$name] = array_values($entries);
        }

        return $out;
    }
}
