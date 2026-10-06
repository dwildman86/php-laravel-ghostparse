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
];
