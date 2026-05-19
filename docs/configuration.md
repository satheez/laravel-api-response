# Configuration

Publish the config:

```bash
php artisan vendor:publish --tag="api-response-config"
```

## Messages

Messages may be translation keys or literal strings.

```php
'messages' => [
    'success' => [
        'default' => 'api-response::messages.success.default',
    ],
],
```

Configured message values must be strings. Invalid message config fails with
`InvalidConfigurationException` instead of returning an empty response message.

## Extra Fields

Static extra fields are appended to every response. Values must be scalar, null, or arrays made from those values.

```php
'extra_fields' => [
    'api_version' => 'v1',
],
```

Dynamic extra fields use resolver classes:

```php
'extra_field_resolvers' => [
    App\Support\RequestIdField::class,
],
```

Resolvers implement `ExtraFieldResolver` and receive `ResponseContext`.
Static field keys and resolver-returned field keys must be strings. Reserved
envelope keys cannot be overridden: `success`, `message`, `data`, `errors`,
and `meta`.

## Macros

Macros are enabled by default:

```php
'macros' => [
    'enabled' => true,
    'replace_existing' => false,
],
```

Macro names must be non-empty strings. Malformed macro config fails during
package boot so deployment issues are visible immediately.

See [macros.md](macros.md).

## Exceptions

Exception rendering is opt-in. Fallback exception messages are hidden unless `app.debug` is true or `exceptions.expose_messages` is enabled.
