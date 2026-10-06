<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel\Events;

use Wildye\EmailParser\Event;
use Wildye\EmailParser\InboundEmail;

/** An inbound email, verified. The sender may retry, so de-duplicate on $email->messageId. */
final class InboundEmailReceived
{
    public function __construct(
        public readonly InboundEmail $email,
        public readonly Event $event,
    ) {
    }
}
