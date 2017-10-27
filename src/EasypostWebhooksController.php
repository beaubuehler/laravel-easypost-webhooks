<?php

namespace BeauB\EasypostWebhooks;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use BeauB\EasypostWebhooks\Middlewares\VerifyBasicAuth;

class EasypostWebhooksController extends Controller
{
    public function __construct()
    {
    }

    public function __invoke(Request $request)
    {
        $eventPayload = $request->input();

        $modelClass = config('easypost-webhooks.model');

        $EasypostWebhookCall = $modelClass::create([
            'description' =>  $eventPayload['description'] ?? '',
            'payload' => $eventPayload,
        ]);

        try {
            $EasypostWebhookCall->process();
        } catch (Exception $exception) {
            $EasypostWebhookCall->saveException($exception);

            throw $exception;
        }
    }
}
