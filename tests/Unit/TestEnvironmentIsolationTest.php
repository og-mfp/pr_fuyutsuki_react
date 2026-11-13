<?php

namespace Tests\Unit;

use Tests\TestCase;

class TestEnvironmentIsolationTest extends TestCase
{
    public function test_tests_use_an_isolated_in_memory_database_even_when_application_configuration_is_cached(): void
    {
        $this->assertSame('testing', config('app.env'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertEmpty(config('database.connections.sqlite.url'));
    }
}
