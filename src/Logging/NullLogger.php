<?php

namespace Firevel\Generator\Logging;

/**
 * Silent logger for headless contexts (tests, queue workers, web admin).
 *
 * Passive methods are no-ops. Interactive methods return their default so
 * generators that prompt for input run to completion without blocking.
 *
 * Captures messages in arrays so tests can assert on them.
 */
class NullLogger implements GeneratorLogger
{
    /** @var string[] */
    public array $info = [];

    /** @var string[] */
    public array $warn = [];

    /** @var string[] */
    public array $error = [];

    public function info(string $message): void
    {
        $this->info[] = $message;
    }

    public function warn(string $message): void
    {
        $this->warn[] = $message;
    }

    public function error(string $message): void
    {
        $this->error[] = $message;
    }

    public function ask(string $question, ?string $default = null): ?string
    {
        return $default;
    }

    public function choice(string $question, array $choices, $default = null): string
    {
        if ($default !== null) {
            // Default may be a value or a key — return the value.
            if (array_key_exists($default, $choices)) {
                return (string) $choices[$default];
            }
            return (string) $default;
        }

        return (string) reset($choices);
    }

    public function confirm(string $question, bool $default = false): bool
    {
        return $default;
    }
}
