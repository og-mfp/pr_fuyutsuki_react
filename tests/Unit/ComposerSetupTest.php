<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ComposerSetupTest extends TestCase
{
    public function test_repeatable_setup_does_not_generate_keys_or_migrate_databases(): void
    {
        /** @var array{scripts: array<string, list<string>>} $composer */
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $setup = implode("\n", $composer['scripts']['setup']);

        $this->assertStringNotContainsString('key:generate', $setup);
        $this->assertStringNotContainsString('migrate', $setup);
    }
}
