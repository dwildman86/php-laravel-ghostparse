<?php

declare(strict_types=1);

namespace Wildye\EmailParser\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Data requests (GDPR subject access requests) on your GhostParse webmail (1.12.0 or later), with WEBMAIL_URL and
 * WEBMAIL_DATA_REQUEST_KEY set:
 *
 *     $request = WebmailDataRequests::create($customer->email, terms: [$customer->name], reference: "DSAR-{$ticket->id}");
 *     $ready = WebmailDataRequests::wait($request['id']);
 *     Storage::put("dsar/{$ticket->id}.zip", WebmailDataRequests::export($ready['id']));
 *
 * @method static array create(string $email, array $terms = [], array $mailboxes = [], \DateTimeInterface|string|null $since = null, \DateTimeInterface|string|null $until = null, ?string $reference = null, bool $includeAttachments = true)
 * @method static array get(string $id)
 * @method static array wait(string $id, float $timeout = 600.0, float $interval = 1.0, ?callable $onProgress = null)
 * @method static string export(string $id, ?string $path = null)
 * @method static array discard(string $id)
 * @method static array list()
 *
 * @see \Wildye\EmailParser\WebmailDataRequests
 */
final class WebmailDataRequests extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'email-parser.webmail-data-requests';
    }
}
