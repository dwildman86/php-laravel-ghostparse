<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Wildye\EmailParser\Client;
use Wildye\EmailParser\Laravel\Http\WebhookController;
use Wildye\EmailParser\WebmailDataRequests;

/**
 * Registers the GhostParse client (resolve Client::class, or use the EmailParser facade) and a webhook route that
 * verifies each delivery and dispatches it as a Laravel event:
 *
 *   - InboundEmailReceived: a parsed email ($event->email is a Wildye\EmailParser\InboundEmail)
 *   - WebhookTestReceived:  the dashboard's "Send test event"
 *
 *     Event::listen(InboundEmailReceived::class, function (InboundEmailReceived $e) {
 *         Ticket::firstOrCreate(['thread_id' => $e->email->threadId])->addReply($e->email->replyText);
 *     });
 *
 * And, with WEBMAIL_URL and WEBMAIL_DATA_REQUEST_KEY set, data requests (GDPR) on your webmail through the
 * WebmailDataRequests facade.
 */
final class EmailParserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/email-parser.php', 'email-parser');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['email-parser'];
            if (empty($config['api_key'])) {
                throw new \RuntimeException('Set EMAIL_PARSER_API_KEY to use the GhostParse API client');
            }

            return new Client($config['api_key'], ['base_url' => $config['base_url']]);
        });
        $this->app->alias(Client::class, 'email-parser');

        // Data requests (GDPR) on your webmail: resolve WebmailDataRequests::class, or use the WebmailDataRequests facade.
        $this->app->singleton(WebmailDataRequests::class, function ($app) {
            $config = $app['config']['email-parser.webmail'] ?? [];
            if (empty($config['url']) || empty($config['data_request_key'])) {
                throw new \RuntimeException('Set WEBMAIL_URL and WEBMAIL_DATA_REQUEST_KEY to use data requests on your webmail');
            }

            return new WebmailDataRequests($config['url'], $config['data_request_key'], ['key_header' => $config['key_header'] ?? 'authorization']);
        });
        $this->app->alias(WebmailDataRequests::class, 'email-parser.webmail-data-requests');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/email-parser.php' => config_path('email-parser.php')], 'email-parser-config');

        $path = $this->app['config']['email-parser.webhook_path'];
        if ($path) {
            // No session, cookies or CSRF: the signature is the authentication.
            Route::post($path, WebhookController::class)->name('email-parser.webhook');
        }
    }
}
