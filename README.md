# Handle Easypost Webhooks in a Laravel application

[EasyPost](https://easypost.com) can notify your application of events using webhooks. This package can help you handle those webhooks. All valid calls will be logged to the database. You can easily define jobs or events that should be dispatched when specific events hit your app.

This package will not handle what should be done after the webhook request has been validated and the right job or event is called. You should still code up any work yourself.

Before using this package we highly recommend reading [the entire documentation on webhooks over at EasyPost](https://www.easypost.com/docs/api.html#webhooks).


## Installation

You can install the package via composer:

```bash
composer require beaub/laravel-easypost-webhooks
```

The service provider will automatically register itself.

You must publish the config file with:
```bash
php artisan vendor:publish --provider="BeauB\EasypostWebhooks\StripeWebhooksServiceProvider" --tag="config"
```

This is the contents of the config file that will be published at `config/easypost-webhooks.php`:

```php
return [

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Easypost event description with the `.` replaced by a `_`.
     *
     * You can find a list of Easypost webhook description types here:
     * https://www.easypost.com/docs/api#possible-event-types.
     */
    'jobs' => [
        // 'refund_successful' => \App\Jobs\EasypostWebhooks\HandleSuccessfulRefund::class,
        // 'tracker_created' => \App\Jobs\EasypostWebhooks\HandleTrackerCreated::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * BeauB\EasypostWebhooks\EasypostWebhookCall.
     */
    'model' => BeauB\EasypostWebhooks\EasypostWebhookCall::class,
];

```

Next, you must publish the migration with:
```bash
php artisan vendor:publish --provider="BeauB\EasypostWebhooks\EasypostWebhooksServiceProvider" --tag="migrations"
```

After the migration has been published you can create the `easypost_webhook_calls` table by running the migrations:

```bash
php artisan migrate
```

Finally, take care of the routing: At [the Easypost dashboard](https://www.easypost.com/account/webhooks-and-events) you must configure at what url Eaypost webhooks should hit your app. In the routes file of your app you must pass that route to `Route::easypostWebhooks`:

```php
Route::easypostWebhooks('webhook-route-configured-at-the-easypost-dashboard');
```

Behind the scenes this will register a `POST` route to a controller provided by this package. Because Easypost has no way of getting a csrf-token, you must add that route to the `except` array of the `VerifyCsrfToken` middleware:

```php
protected $except = [
    'webhook-route-configured-at-the-easypost-dashboard',
];
```

## Usage

Easypost will send out webhooks for several event types. You can find the [full list of events types](https://www.easypost.com/docs/api#possible-event-types) in the Easypost documentation.

Unlike Stripe, Easypost does not sign the webhooks it sends to your server. In order to ensure that requests are valid, [use Basic Authentication and HTTPS on your endpoint](https://www.easypost.com/docs/api.html#webhooks). This package does not include this functionality. Refer to the Lavavel documentation regarding [Basic Authentication] (https://laravel.com/docs/5.5/authentication#http-basic-authentication) to secure your endpoint.

Unless something goes terribly wrong, this package will always respond with a `200` to webhook requests. Sending a `200` will prevent Easypost from resending the same event over and over again. All webhook requests will be logged in the `easypost_webhook_calls` table. The table has a `payload` column where the entire payload of the incoming webhook is saved.

If something goes wrong during the webhook request the thrown exception will be saved in the `exception` column. In that case the controller will send a `500` instead of `200`.

There are two ways this package enables you to handle webhook requests: you can opt to queue a job or listen to the events the package will fire.


### Handling webhook requests using jobs
If you want to do something when a specific event type comes in you can define a job that does the work. Here's an example of such a job:

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use BeauB\EasypostWebhooks\EasypostWebhookCall;

class HandleChargeableSource implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** @var \BeauB\EasypostWebhooks\EasypostWebhookCall */
    public $webhookCall;

    public function __construct(EasypostWebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    public function handle()
    {
        // do your work here

        // you can access the payload of the webhook call with `$this->webhookCall->payload`
    }
}
```

We highly recommend that you make this job queueable, because this will minimize the response time of the webhook requests. This allows you to handle more Easypost webhook requests and avoid timeouts.

After having created your job you must register it at the `jobs` array in the `easypost-webhooks.php` config file. The key should be the name of [the easypost event type](https://www.easypost.com/docs/api#possible-event-types) where but with the `.` replaced by `_`. The value should be the fully qualified classname.

```php
// config/easypost-webhooks.php

'jobs' => [
    'tracker_created' => \App\Jobs\EasypostWebhooks\HandleTrackerCreated::class,
],
```

### Handling webhook requests using events

Instead of queueing jobs to perform some work when a webhook request comes in, you can opt to listen to the events this package will fire. Whenever a valid request hits your app, the package will fire a `easypost-webhooks::<name-of-the-event>` event.

The payload of the events will be the instance of `EasypostWebhookCall` that was created for the incoming request.

Let's take a look at how you can listen for such an event. In the `EventServiceProvider` you can register listeners.

```php
/**
 * The event listener mappings for the application.
 *
 * @var array
 */
protected $listen = [
    'easypost-webhooks::tracker.created' => [
        App\Listeners\TrackerCreated::class,
    ],
];
```

Here's an example of such a listener:

```php
<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use BeauB\EasypostWebhooks\EasypostWebhookCall;

class TrackerCreated implements ShouldQueue
{
    public function handle(EasypostWebhookCall $webhookCall)
    {
        // do your work here

        // you can access the payload of the webhook call with `$webhookCall->payload`
    }   
}
```

We highly recommend that you make the event listener queueable, as this will minimize the response time of the webhook requests. This allows you to handle more Easypost webhook requests and avoid timeouts.

The above example is only one way to handle events in Laravel. To learn the other options, read [the Laravel documentation on handling events](https://laravel.com/docs/5.5/events).

## Advanced usage

### Retry handling a webhook

All incoming webhook requests are written to the database. This is incredibly valuable when something goes wrong while handling a webhook call. You can easily retry processing the webhook call, after you've investigated and fixed the cause of failure, like this:

```php
use BeauB\EasypostWebhooks\EasypostWebhookCall;

EasypostWebhookCall::find($id)->process();
```

### Performing custom logic

You can add some custom logic that should be executed before and/or after the scheduling of the queued job by using your own model. You can do this by specifying your own model in the `model` key of the `easypost-webhooks` config file. The class should extend `BeauB\EasypostWebhooks\EasypostWebhookCall`.

Here's an example:

```php
use BeauB\EasypostWebhooks\EasypostWebhookCall;

class MyCustomWebhookCall extends EasyposteWebhookCall
{
    public function process()
    {
        // do some custom stuff beforehand

        parent::process();

        // do some custom stuff afterwards
    }
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information about what has changed recently.

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

This package was modified from the [laravel-stripe-webhooks package](https://github.com/spatie/laravel-stripe-webhooks) and republished by [Beau Buehler](https://github.com/beaubuehler) to work with the Easypost webhook.

Original Work:
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

A big thank you to [Sebastiaan Luca](https://twitter.com/sebastiaanluca) who generously shared his Stripe webhook solution that inspired this package.


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
