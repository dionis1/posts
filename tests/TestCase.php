<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests exercise server behavior and should not require
        // production Vite assets to have been built beforehand.
        $this->withoutVite();
    }
}
