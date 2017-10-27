<?php

namespace BeauB\EasypostWebhooks;

use Exception;
use Illuminate\Database\Eloquent\Model;
use BeauB\EasypostWebhooks\Exceptions\WebhookFailed;

class EasypostWebhookCall extends Model
{
    public $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'exception' => 'array',
    ];

    public function process()
    {
        $this->clearException();

        if ($this->description === '') {
            throw WebhookFailed::missingDescription($this);
        }

        event("easypost-webhooks::{$this->description}", $this);

        $jobClass = $this->determineJobClass($this->description);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this);
        }

        dispatch(new $jobClass($this));
    }

    public function saveException(Exception $exception)
    {
        $this->exception = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->save();

        return $this;
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        return config("easypost-webhooks.jobs.{$jobConfigKey}", '');
    }

    protected function clearException()
    {
        $this->exception = null;

        $this->save();

        return $this;
    }
}
