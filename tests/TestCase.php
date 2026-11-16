<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $database = config('database.connections.'.config('database.default').'.database');
        if (! is_string($database) || ! preg_match('/(?:test|testing)/i', $database)) {
            throw new \RuntimeException('Gunakan database khusus pengujian dengan nama yang mengandung test atau testing.');
        }

        $this->withoutVite();
    }
}
