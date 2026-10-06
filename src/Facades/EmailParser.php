<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * The GhostParse API client.
 *
 * @method static ?string inbox()
 * @method static array sendSample(?string $to = null)
 * @method static string replyAddress(string $address, string $tag)
 * @method static array deliveries(?string $status = null, ?string $domainId = null, ?string $messageId = null, ?int $limit = null, ?int $before = null)
 * @method static \Wildye\EmailParser\Domains domains()
 * @method static \Wildye\EmailParser\Accounts accounts()
 * @method static \Wildye\EmailParser\Client forAccount(string $accountId)
 * @method static array webhookSettings()
 * @method static array updateWebhookSettings(array $settings)
 *
 * @see \Wildye\EmailParser\Client
 */
final class EmailParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'email-parser';
    }
}
