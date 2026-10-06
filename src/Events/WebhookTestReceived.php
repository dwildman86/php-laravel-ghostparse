<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel\Events;

use Wildye\EmailParser\Event;

/** The dashboard's (or API's) "Send test event". */
final class WebhookTestReceived
{
    public function __construct(public readonly Event $event)
    {
    }
}
