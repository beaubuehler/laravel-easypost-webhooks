<?php

return [

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Easypost event description with the `.` replaced by a `_`.
     *
     * You can find a list of Easypost webhook types here:
     * https://www.easypost.com/docs/api#possible-event-types.
     */
    'jobs' => [
        // 'source_chargeable' => \App\Jobs\EasypostWebhooks\HandleChargeableSource::class,
        // 'charge_failed' => \App\Jobs\EasypostWebhooks\HandleFailedCharge::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * BeauB\EasypostWebhooks\EasypostWebhookCall.
     */
    'model' => BeauB\EasypostWebhooks\EasypostWebhookCall::class,
];
