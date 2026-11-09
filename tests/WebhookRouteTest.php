<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;
use Wildye\EmailParser\Client;
use Wildye\EmailParser\Laravel\EmailParserServiceProvider;
use Wildye\EmailParser\Laravel\Events\InboundEmailReceived;
use Wildye\EmailParser\Laravel\Events\WebhookTestReceived;
use Wildye\EmailParser\Laravel\Facades\EmailParser;
use Wildye\EmailParser\Thread;
use Wildye\EmailParser\Webhook;
use Wildye\EmailParser\WebmailDataRequests;
use Wildye\EmailParser\Laravel\Facades\WebmailDataRequests as WebmailDataRequestsFacade;

final class WebhookRouteTest extends TestCase
{
    /** The shared fixtures: bundled in tests/fixtures in the package's own repository, in sdk/fixtures in the monorepo. */
    private static function fixtures(): string
    {
        return is_dir(__DIR__ . '/fixtures') ? __DIR__ . '/fixtures' : __DIR__ . '/../../fixtures';
    }

    protected function getPackageProviders($app): array
    {
        return [EmailParserServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $secrets = json_decode((string) file_get_contents(self::fixtures() . '/secrets.json'), true);
        $app['config']->set('email-parser.webhook_secret', $secrets['secret']);
        $app['config']->set('email-parser.api_key', 'ek_live_test');
        // The fixture was signed at a fixed time.
        $app['config']->set('email-parser.tolerance', 0);
    }

    private static function headers(): array
    {
        $h = json_decode((string) file_get_contents(self::fixtures() . '/email.received.headers.json'), true);

        return array_combine(array_map(static fn ($k) => 'HTTP_' . strtoupper(str_replace('-', '_', $k)), array_keys($h)), $h);
    }

    public function testDispatchesAVerifiedInboundEmail(): void
    {
        Event::fake();
        $body = (string) file_get_contents(self::fixtures() . '/email.received.json');
        $this->call('POST', '/email-parser/webhook', [], [], [], self::headers() + ['CONTENT_TYPE' => 'application/json'], $body)->assertOk();

        Event::assertDispatched(InboundEmailReceived::class, function (InboundEmailReceived $e) {
            return $e->email->subject === 'Re: Order #1042'
                && $e->email->threadId === Thread::idFor('<out_1@acme.test>')
                && $e->email->field('order_number') === 1042;
        });
    }

    public function testRejectsABadSignature(): void
    {
        Event::fake();
        $body = str_replace('Order #1042', 'Order #9999', (string) file_get_contents(self::fixtures() . '/email.received.json'));
        $this->call('POST', '/email-parser/webhook', [], [], [], self::headers() + ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
        Event::assertNotDispatched(InboundEmailReceived::class);
    }

    public function testTestEvents(): void
    {
        Event::fake();
        $secret = config('email-parser.webhook_secret');
        $body = '{"id":"evt_t","type":"webhook.test","created_at":"2026-10-03T00:00:00Z","tenant_id":"acct_1","data":{"message":"hi"}}';
        $h = Webhook::signStandard($body, 'evt_t', $secret);
        $server = ['HTTP_WEBHOOK_ID' => $h['webhook-id'], 'HTTP_WEBHOOK_TIMESTAMP' => $h['webhook-timestamp'], 'HTTP_WEBHOOK_SIGNATURE' => $h['webhook-signature'], 'CONTENT_TYPE' => 'application/json'];
        $this->call('POST', '/email-parser/webhook', [], [], [], $server, $body)->assertOk();
        Event::assertDispatched(WebhookTestReceived::class);
    }

    public function testClientAndFacade(): void
    {
        self::assertInstanceOf(Client::class, $this->app->make(Client::class));
        self::assertSame($this->app->make(Client::class), EmailParser::getFacadeRoot());
    }

    public function testWebmailDataRequestsFacade(): void
    {
        config(['email-parser.webmail.url' => 'https://example.com/mail', 'email-parser.webmail.data_request_key' => 'wmk_test']);
        $dsar = $this->app->make(WebmailDataRequests::class);
        self::assertSame($dsar, WebmailDataRequestsFacade::getFacadeRoot());
    }

    public function testWebmailDataRequestsNeedsItsSettings(): void
    {
        $this->expectExceptionMessage('WEBMAIL_URL and WEBMAIL_DATA_REQUEST_KEY');
        $this->app->make(WebmailDataRequests::class);
    }
}
