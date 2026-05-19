# Changelog

All notable changes to `laravel-api-response` will be documented in this file.

## 2.0.0 - Unreleased

- Rewrites the package around a stateless response factory.
- Renames the package to `satheez/laravel-api-response`.
- Adds the standard `{ success, message, data, errors, meta }` response envelope.
- Adds configurable top-level extra fields and resolver classes.
- Adds Laravel translation support for default response messages.
- Adds response macros, a fluent builder, resource/pagination helpers, and optional exception rendering.
- Targets Laravel 12 and 13.
