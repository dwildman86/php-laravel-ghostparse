# Changelog

All notable changes to `wildye/email-parser-laravel`. Versions follow [semantic versioning](https://semver.org).

## 1.1.0

- Requires `wildye/email-parser` ^1.2, for `Email::fromName()` and `Email::signature()` when sending through the
  `EmailParser` facade, and `SentEmail::$fromName`.
- README: sending with a From name.

## 1.0.0

- First release: a verified webhook route that dispatches `InboundEmailReceived` and `WebhookTestReceived` events,
  the `EmailParser` facade for the API, and config publishing.
