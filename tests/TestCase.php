<?php

namespace Fanout\LaravelGrip\Tests;

use Fanout\LaravelGrip\LaravelGripServiceProvider;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

const ECHO_LEVELS = [
    // 'debug',
    'info',
    'notice',
    'warning',
    'error',
    'critical',
    'alert',
];

class TestCase extends \Orchestra\Testbench\TestCase {
    public function setUp(): void {
        parent::setUp();
        // additional setup
        Log::listen(function( MessageLogged $logged ) {
            if (!in_array( $logged->level, ECHO_LEVELS ) ) {
                return;
            }
            echo $logged->level . ": " . $logged->message . "\n";
        });
    }

    protected function getPackageProviders($app) {
        return [
            LaravelGripServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app) {
        // perform environment setup
    }
}
