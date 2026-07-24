<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        // Automatically restore default users and menu permissions after test completion
        try {
            Artisan::call('db:seed', ['--force' => true]);
        } catch (\Throwable $e) {
            // Ignore error
        }
    }
}
