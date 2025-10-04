<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable broadcasting for tests to avoid Pusher configuration issues
        config(['broadcasting.default' => 'null']);
    }
}
