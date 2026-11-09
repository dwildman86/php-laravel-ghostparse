<?php

return [
    // API key from the dashboard (API keys page). Needed only to call the API (EmailParser::inbox(), domains, …).
    'api_key' => env('EMAIL_PARSER_API_KEY'),

    // Your own installation, or the hosted service.
    'base_url' => env('EMAIL_PARSER_URL', 'https://ghostparse.com'),

    // Webhook signing secret (Inbound webhook page). Required for the webhook route.
    'webhook_secret' => env('EMAIL_PARSER_WEBHOOK_SECRET'),

    // Where the package listens for webhooks. Point your GhostParse webhook URL at https://your-app/{path}.
    // Set to null to register the route yourself (see WebhookController).
    'webhook_path' => env('EMAIL_PARSER_WEBHOOK_PATH', 'email-parser/webhook'),

    // Seconds a signature stays valid (replay protection).
    'tolerance' => 300,

    // Data requests (GDPR) on your GhostParse webmail (1.12.0 or later): where it is (https://example.com/mail) and an
    // API key made in the webmail under Settings → Data requests → From another system (wmk_…). Needed only for the
    // WebmailDataRequests facade. key_header 'x-api-key' is for hosting that drops the Authorization header.
    'webmail' => [
        'url' => env('WEBMAIL_URL'),
        'data_request_key' => env('WEBMAIL_DATA_REQUEST_KEY'),
        'key_header' => env('WEBMAIL_KEY_HEADER', 'authorization'),
    ],
];
