<?php

namespace Firevel\Generator\Tests\Logging;

use Firevel\Generator\Logging\GeneratorLogger;
use Firevel\Generator\Logging\NullLogger;
use PHPUnit\Framework\TestCase;

class NullLoggerTest extends TestCase
{
    public function test_implements_generator_logger_interface()
    {
        $this->assertInstanceOf(GeneratorLogger::class, new NullLogger());
    }

    public function test_passive_methods_capture_messages()
    {
        $logger = new NullLogger();
        $logger->info('hello');
        $logger->warn('careful');
        $logger->error('oops');

        $this->assertSame(['hello'], $logger->info);
        $this->assertSame(['careful'], $logger->warn);
        $this->assertSame(['oops'], $logger->error);
    }

    public function test_ask_returns_default()
    {
        $logger = new NullLogger();
        $this->assertSame('fallback', $logger->ask('Name?', 'fallback'));
        $this->assertNull($logger->ask('Name?'));
    }

    public function test_choice_returns_default_value_when_key_provided()
    {
        $logger = new NullLogger();
        $this->assertSame('blue', $logger->choice('Pick', ['red', 'blue', 'green'], 1));
    }

    public function test_choice_returns_default_value_when_value_provided()
    {
        $logger = new NullLogger();
        $this->assertSame('green', $logger->choice('Pick', ['red', 'blue', 'green'], 'green'));
    }

    public function test_choice_falls_back_to_first_when_no_default()
    {
        $logger = new NullLogger();
        $this->assertSame('red', $logger->choice('Pick', ['red', 'blue', 'green']));
    }

    public function test_confirm_returns_default()
    {
        $logger = new NullLogger();
        $this->assertTrue($logger->confirm('Sure?', true));
        $this->assertFalse($logger->confirm('Sure?', false));
        $this->assertFalse($logger->confirm('Sure?'));
    }
}
