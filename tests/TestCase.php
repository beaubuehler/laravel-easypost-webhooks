<?php

namespace BeauB\EasypostWebhooks\Tests;

use Exception;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use BeauB\EasypostWebhooks\EasypostWebhooksServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * Set up the environment.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setUpDatabase()
    {
        include_once __DIR__.'/../database/migrations/create_easypost_webhook_calls_table.php.stub';

        (new \CreateEasypostWebhookCallsTable())->up();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            EasypostWebhooksServiceProvider::class,
        ];
    }

    protected function disableExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct()
            {
            }

            public function report(Exception $e)
            {
            }

            public function render($request, Exception $exception)
            {
                throw $exception;
            }
        });
    }
}
