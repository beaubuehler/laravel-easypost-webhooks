<?php

namespace BeauB\EasypostWebhooks\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use BeauB\EasypostWebhooks\EasypostWebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Event::fake();

        Bus::fake();

        Route::EasypostWebhooks('easypost-webhooks');

        config(['easypost-webhooks.jobs' => ['my_description' => DummyJob::class]]);
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $payload = [
            'description' => 'my.description',
            'key' => 'value',
        ];

        $this
            ->postJson('easypost-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, EasypostWebhookCall::get());

        $webhookCall = EasypostWebhookCall::first();

        $this->assertEquals('my.description', $webhookCall->description);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('easypost-webhooks::my.description', function ($event, $eventPayload) use ($webhookCall) {
            if (! $eventPayload instanceof EasypostWebhookCall) {
                return false;
            }

            return $eventPayload->id === $webhookCall->id;
        });

        Bus::assertDispatched(DummyJob::class, function (DummyJob $job) use ($webhookCall) {
            return $job->EasypostWebhookCall->id === $webhookCall->id;
        });
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];

        $this
            ->postJson('easypost-webhooks', $payload)
            ->assertStatus(400);

        $this->assertCount(1, EasypostWebhookCall::get());

        $webhookCall = EasypostWebhookCall::first();

        $this->assertEquals('', $webhookCall->description);
        $this->assertEquals(['invalid_payload'], $webhookCall->payload);
        $this->assertEquals('Webhook call id `1` did not contain a description. Valid Easypost webhook calls should always contain a description.', $webhookCall->exception['message']);

        Event::assertNotDispatched('easypost-webhooks::my.type');

        Bus::assertNotDispatched(DummyJob::class);
    }
}
