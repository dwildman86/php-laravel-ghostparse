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
        ->from('jane@acme.example')
        ->fromName('Jane Smith')   // "Jane Smith <jane@acme.example>"
        ->to('customer@example.com')
        ->subject('Your order has shipped')
        ->text('Hi, your order is on its way.')
);
$sent->fromName; // "Jane Smith"
```

The From name comes from `fromName()`, a name in `from`, or the address's saved From name (set in the dashboard), in that order. The response's `from` shows the one used. Sending is only available on installations that enable it.

## Call the API

```php
use Wildye\EmailParser\Laravel\Facades\EmailParser;

EmailParser::sendSample();
EmailParser::forAccount($subAccountId)->domains()->create('bobsbakery.com', inboundEnabled: true); // resellers
```

Publish the config with `php artisan vendor:publish --tag=email-parser-config`.

## Data requests on your webmail (GDPR)

Find everything about one person on your GhostParse webmail (1.12.0 or later) and export it as a ZIP, for a subject access request: every mailbox and folder, email they sent or received, and email that mentions their address or your extra terms. It uses a webmail API key (`wmk_…`) made in the webmail under **Settings → Data requests → From another system**, not your GhostParse key. Review the ZIP and redact other people's data before you send it.

Set the webmail's address and key in `.env`:

```dotenv
WEBMAIL_URL=https://example.com/mail
WEBMAIL_DATA_REQUEST_KEY=wmk_…
```

```php
use Wildye\EmailParser\Laravel\Facades\WebmailDataRequests;

// A "GDPR request" button on a ticket
$request = WebmailDataRequests::create($customer->email, terms: [$customer->name], reference: "DSAR-{$ticket->id}");
$ready = WebmailDataRequests::wait($request['id']);
Storage::put("dsar/{$ticket->id}.zip", WebmailDataRequests::export($ready['id']));
```

For a long search, start it in one job and check it with `WebmailDataRequests::get($id)` from a scheduled job instead
of waiting.

A request's `status` is `searching`, `ready` or `expired`; while it's searching, each read carries the search on and reports `progress`. The export is refused with 409 (`not_ready`) while it's searching and 410 (`expired`) once its results have been removed (after 30 days, or when discarded). If your hosting drops the Authorization header, send the key as X-Api-Key (`WEBMAIL_KEY_HEADER=x-api-key`).
