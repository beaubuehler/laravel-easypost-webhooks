<?php

namespace BeauB\EasypostWebhooks\Tests;

use BeauB\EasypostWebhooks\EasypostWebhookCall;

class DummyJob
{
    /** @var \BeauB\EasypostWebhooks\EasypostWebhookCall */
    public $EasypostWebhookCall;

    public function __construct(EasypostWebhookCall $EasypostWebhookCall)
    {
        $this->EasypostWebhookCall = $EasypostWebhookCall;
    }

    public function handle()
    {
    }
}
