<?php

namespace BeauB\EasypostWebhooks\Tests;

use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use BeauB\EasypostWebhooks\EasypostWebhookCall;

class EasypostWebhookCallTest extends TestCase
{
    /** @var \BeauB\EasypostWebhooks\EasypostWebhookCall */
    public $EasypostWebhookCall;

    public function setUp()
    {
        parent::setUp();

        Bus::fake();

        Event::fake();

        config(['easypost-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->EasypostWebhookCall = EasypostWebhookCall::create([
            'description' => 'my.type',
            'payload' => ['name' => 'value'],
        ]);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->EasypostWebhookCall->process();

        Bus::assertDispatched(DummyJob::class, function (DummyJob $job) {
            return $job->EasypostWebhookCall->id === $this->EasypostWebhookCall->id;
        });
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['easypost-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->EasypostWebhookCall->process();

        Bus::assertNotDispatched(DummyJob::class);
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['easypost-webhooks.jobs' => []]);

        $this->EasypostWebhookCall->process();

        Bus::assertNotDispatched(DummyJob::class);
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['easypost-webhooks.jobs' => ['another_description' => DummyJob::class]]);

        $this->EasypostWebhookCall->process();

        $webhookCall = $this->EasypostWebhookCall;

        Event::assertDispatched("easypost-webhooks::{$webhookCall->description}", function ($event, $eventPayload) use ($webhookCall) {
            if (! $eventPayload instanceof EasypostWebhookCall) {
                return false;
            }

            return $eventPayload->id === $webhookCall->id;
        });
    }

    /** @test */
    public function it_can_save_an_exception()
    {
        $this->EasypostWebhookCall->saveException(new Exception('my message', 123));

        $this->assertEquals(123, $this->EasypostWebhookCall->exception['code']);
        $this->assertEquals('my message', $this->EasypostWebhookCall->exception['message']);
        $this->assertGreaterThan(200, strlen($this->EasypostWebhookCall->exception['trace']));
    }

    /** @test */
    public function processing_a_webhook_will_clear_the_exception()
    {
        $this->EasypostWebhookCall->saveException(new Exception('my message', 123));

        $this->EasypostWebhookCall->process();

        $this->assertNull($this->EasypostWebhookCall->exception);
    }
}
