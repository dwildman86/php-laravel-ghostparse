<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel\Http;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Wildye\EmailParser\Exception\SignatureVerificationException;
use Wildye\EmailParser\Laravel\Events\InboundEmailReceived;
use Wildye\EmailParser\Laravel\Events\WebhookTestReceived;
use Wildye\EmailParser\Webhook;

/**
 * Verifies an GhostParse webhook (Standard Webhooks or the original format; JSON or multipart) and dispatches it.
 * Listeners run before the response, so an exception in one makes the delivery fail and the sender retry later:
 * nothing is lost. Make listeners idempotent (de-duplicate on $email->messageId), or queue them.
 */
final class WebhookController
{
    public function __invoke(Request $request, Dispatcher $events): Response
    {
        $secret = (string) config('email-parser.webhook_secret');
        if ($secret === '') {
            abort(500, 'EMAIL_PARSER_WEBHOOK_SECRET is not set');
        }
        $headers = [
            'webhook-id' => (string) $request->header('webhook-id'),
            'webhook-timestamp' => (string) $request->header('webhook-timestamp'),
            'webhook-signature' => (string) $request->header('webhook-signature'),
        ];
        $tolerance = (int) config('email-parser.tolerance', Webhook::DEFAULT_TOLERANCE);

        try {
            $multipart = $request->has('event') && str_starts_with((string) $request->header('content-type'), 'multipart/form-data');
            $event = $multipart
                ? Webhook::verifyHeaders((string) $request->input('event'), $headers, $secret, $tolerance)->withFiles(
                    array_map(static fn ($file) => $file->getRealPath(), $request->allFiles()),
                )
                : Webhook::verifyHeaders($request->getContent(), $headers, $secret, $tolerance);
        } catch (SignatureVerificationException $e) {
            return new Response($e->getMessage(), 400);
        }

        if ($event->isEmailReceived()) {
            $events->dispatch(new InboundEmailReceived($event->email(), $event));
        } elseif ($event->type === 'webhook.test') {
            $events->dispatch(new WebhookTestReceived($event));
        }

        return new Response('', 200);
    }
}
