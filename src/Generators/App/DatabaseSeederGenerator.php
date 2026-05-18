<?php

namespace Firevel\Generator\Generators\App;

use Firevel\Generator\Generators\BaseGenerator;

/**
 * Writes `database/seeders/DatabaseSeeder.php` so it calls every data
 * seeder DataSeedersGenerator emitted, in input order. If no group
 * produced a file, no DatabaseSeeder is written.
 */
class DatabaseSeederGenerator extends BaseGenerator
{
    public static function description(): string
    {
        return 'Generates DatabaseSeeder.php that calls each emitted <Group>DataSeeder in input order.';
    }

    public function handle()
    {
        $classes = $this->context->get('seeders.emitted_classes', []);

        if (! is_array($classes) || $classes === []) {
            return;
        }

        $source = $this->render('app/database-seeder', [
            'classes' => array_values($classes),
        ]);

        $this->createFile(
            database_path('seeders') . '/DatabaseSeeder.php',
            $source
        );
    }
}
