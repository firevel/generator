<?php

namespace Firevel\Generator\Logging;

/**
 * Surface used by generators to talk to the user.
 *
 * Laravel's `Illuminate\Console\Command` already satisfies this contract
 * structurally, so `setLogger($this)` from a Command keeps working. The
 * interface exists so non-CLI contexts (queue workers, HTTP admins, tests)
 * can supply their own implementation — see {@see NullLogger}.
 */
interface GeneratorLogger
{
    /** Informational message — the user reads it; no input required. */
    public function info(string $message): void;

    /** Warning — something looks off but the run continues. */
    public function warn(string $message): void;

    /** Error — typically printed before aborting. */
    public function error(string $message): void;

    /**
     * Ask the user a free-form question. Implementations running headless
     * should return $default.
     */
    public function ask(string $question, ?string $default = null): ?string;

    /**
     * Ask the user to pick from a list. $default may be the value or the key.
     * Implementations running headless should return $default.
     */
    public function choice(string $question, array $choices, $default = null): string;

    /**
     * Ask a yes/no question. Implementations running headless should return
     * $default.
     */
    public function confirm(string $question, bool $default = false): bool;
}
