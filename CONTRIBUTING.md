# Contributing

Thank you for your interest in contributing to Laravel API Response.

## Local Setup

```bash
git clone https://github.com/satheez/laravel-api-response.git
cd laravel-api-response
composer install
```

## Running Tests

```bash
vendor/bin/pest
```

To run a specific test file or filter by test name:

```bash
vendor/bin/pest --filter="ExtraFieldsResolver"
```

To check test coverage (requires PCOV or Xdebug):

```bash
composer test-coverage
```

## Code Style

Format code with Pint:

```bash
vendor/bin/pint
```

To check without fixing:

```bash
vendor/bin/pint --test
```

## Static Analysis

Run PHPStan:

```bash
vendor/bin/phpstan analyse
```

## Composer Scripts

| Script | Purpose |
| --- | --- |
| `composer test` | Run Pest tests |
| `composer test-coverage` | Run tests with coverage (minimum 90%) |
| `composer analyse` | Run PHPStan static analysis |
| `composer format` | Fix code style with Pint |
| `composer format-test` | Check code style without fixing |

## Branch Naming

- `feature/<name>` — new features
- `fix/<name>` — bug fixes
- `chore/<name>` — tooling, dependency updates, documentation

## Pull Request Guidelines

- Keep PRs focused on a single concern.
- All new code must be covered by tests.
- PHPStan must pass at the configured level.
- Pint formatting must be clean.
- Add an entry to `CHANGELOG.md` under `[Unreleased]`.

## Testing Expectations

- Unit and Feature tests are written using **Pest PHP**.
- Integration tests use **Orchestra Testbench**.
- Use test fixtures for stubbing response scenarios.
- Test both success and error paths for any new response methods.
