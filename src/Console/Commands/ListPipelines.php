<?php

namespace Firevel\Generator\Console\Commands;

use Firevel\Generator\FirevelGeneratorManager;
use Firevel\Generator\Generators\BaseGenerator;
use Illuminate\Console\Command;

class ListPipelines extends Command
{
    protected $signature = 'firevel:generate:list {pipeline? : Show steps for this pipeline only}';

    protected $description = 'List available generator pipelines and their steps.';

    public function handle()
    {
        $manager = app(FirevelGeneratorManager::class);
        $name = $this->argument('pipeline');

        $errors = $manager->validate();
        if (!empty($errors)) {
            $this->warn('Pipeline registry has errors (commands referencing these will fail):');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
            $this->line('');
        }

        if ($name !== null) {
            return $this->showPipeline($manager, $name);
        }

        return $this->listAll($manager);
    }

    protected function listAll(FirevelGeneratorManager $manager): int
    {
        $descriptions = $manager->getDescriptions();

        if (empty($descriptions)) {
            $this->warn('No pipelines configured.');
            return 0;
        }

        $rows = [];
        foreach ($descriptions as $pipelineName => $description) {
            $rows[] = [$pipelineName, $description === '' ? '<comment>(no description)</comment>' : $description];
        }

        $this->table(['Pipeline', 'Description'], $rows);
        $this->line('');
        $this->line('Run <info>php artisan firevel:generate:list [pipeline]</info> to see its steps.');

        return 0;
    }

    protected function showPipeline(FirevelGeneratorManager $manager, string $name): int
    {
        $pipeline = $manager->getPipeline($name);

        if ($pipeline === null) {
            $this->error("Pipeline '{$name}' is not configured.");
            return 1;
        }

        $this->line("<info>Pipeline:</info> {$name}");
        if ($pipeline['description'] !== '') {
            $this->line("<info>Description:</info> {$pipeline['description']}");
        }
        $this->line('');

        if (empty($pipeline['steps'])) {
            $this->warn('This pipeline has no steps.');
            return 0;
        }

        $rows = [];
        foreach ($pipeline['steps'] as $key => $step) {
            $rows[] = $this->describeStep($key, $step);
        }

        $this->table(['Step', 'Type', 'Description'], $rows);

        return 0;
    }

    /**
     * Render a single step as a [name, type, description] row.
     *
     * @param int|string $key
     * @param mixed $step
     * @return array{0:string,1:string,2:string}
     */
    protected function describeStep($key, $step): array
    {
        if (is_string($step) && class_exists($step)) {
            $label = is_string($key) ? $key : $this->shortClassName($step);
            $description = $this->classDescription($step);
            return [$label, $this->shortClassName($step), $description];
        }

        if (is_array($step) && isset($step['scope'], $step['pipeline'])) {
            $label = is_string($key) ? $key : "scope: {$step['scope']}";
            $description = "Runs pipeline `{$step['pipeline']}` over scope `{$step['scope']}`.";
            return [$label, 'scoped', $description];
        }

        return [
            is_string($key) ? $key : (string) $key,
            'unknown',
            '<comment>(unrecognized step shape)</comment>',
        ];
    }

    protected function classDescription(string $class): string
    {
        if (is_subclass_of($class, BaseGenerator::class)) {
            $description = $class::description();
            return $description !== '' ? $description : '<comment>(no description)</comment>';
        }

        return '<comment>(not a BaseGenerator)</comment>';
    }

    protected function shortClassName(string $class): string
    {
        $pos = strrpos($class, '\\');
        return $pos === false ? $class : substr($class, $pos + 1);
    }
}
