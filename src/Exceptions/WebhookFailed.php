<?php

namespace BeauB\EasypostWebhooks\Exceptions;

use Exception;
use BeauB\EasypostWebhooks\EasypostWebhookCall;

class WebhookFailed extends Exception
{
    public static function missingSignature()
    {
        return new static('The request did not contain a header named `Stripe-Signature`.');
    }

    public static function invalidSignature($signature)
    {
        return new static("The signature `{$signature}` found in the header named `Stripe-Signature` is invalid. Make sure that the `services.stripe.webhook_signing_secret` config key is set to the value you found on the Stripe dashboard. If you are caching your config try running `php artisan clear:cache` to resolve the problem.");
    }

    public static function signingSecretNotSet()
    {
        return new static('The Stripe webhook signing secret is not set. Make sure that the `services.stripe.webhook_signing_secret` config key is set to the value you found on the Stripe dashboard.');
    }

    public static function jobClassDoesNotExist(string $jobClass, EasypostWebhookCall $webhookCall)
    {
        return new static("Could not process webhook id `{$webhookCall->id}` of description `{$webhookCall->description} because the configured jobclass `$jobClass` does not exist.");
    }

    public static function missingDescription(EasypostWebhookCall $webhookCall)
    {
        return new static("Webhook call id `{$webhookCall->id}` did not contain a description. Valid Easypost webhook calls should always contain a description.");
    }

    public function render($request)
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
