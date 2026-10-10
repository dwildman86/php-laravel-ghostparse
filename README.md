# GhostParse for Laravel

A verified webhook route that turns inbound email into Laravel events, plus the API client as a facade. Laravel
10, 11 and 12.

```sh
composer require wildye/email-parser-laravel
```

`.env`:

```
EMAIL_PARSER_WEBHOOK_SECRET=whsec_…   # Inbound webhook page
EMAIL_PARSER_API_KEY=ek_live_…        # optional: only to call the API
```

Set your webhook URL (dashboard, Inbound webhook) to `https://your-app.example/email-parser/webhook`. Change the
path with `EMAIL_PARSER_WEBHOOK_PATH`. The route needs no CSRF token or session: the signature authenticates it.

## Listen for email

```php
use Illuminate\Support\Facades\Event;
use Wildye\EmailParser\Laravel\Events\InboundEmailReceived;

Event::listen(function (InboundEmailReceived $event) {
    $email = $event->email; // Wildye\EmailParser\InboundEmail
    Ticket::firstOrCreate(['thread_id' => $email->threadId])
        ->replies()->firstOrCreate(['message_id' => $email->messageId], [
            'from' => $email->from?->address,
            'body' => $email->replyText,
            'order_number' => $email->field('order_number'),
        ]);
});
```

Listeners run before the response is sent, so if one throws, the delivery fails and the sending server retries:
nothing is lost. Make them idempotent (the `firstOrCreate` on `message_id` above), or queue them
(`implements ShouldQueue`). Signature failures get a 400 and dispatch nothing. `WebhookTestReceived` is dispatched
for the dashboard's "Send test event".

## Send email

```php
use Wildye\EmailParser\Email;
use Wildye\EmailParser\Laravel\Facades\EmailParser;

$sent = EmailParser::send(
    Email::create()
        ->from('derek@1stkirkby.example')
        ->fromName('Derek Wildman')   // "Derek Wildman <derek@1stkirkby.example>"
        ->to('parent@example.com')
        ->subject('Camp kit list')
        ->text('Hi, the kit list is attached.')
);
$sent->fromName; // "Derek Wildman"
```

The From name comes from `fromName()`, a name in `from`, or the address's saved From name (set in the dashboard), in that order. The response's `from` shows the one used. Sending is only available on installations that enable it.

## Call the API

```php
use Wildye\EmailParser\Laravel\Facades\EmailParser;

EmailParser::sendSample();
EmailParser::forAccount($subAccountId)->domains()->create('bobsbakery.com', inboundEnabled: true); // resellers
```

Publish the config with `php artisan vendor:publish --tag=email-parser-config`.
