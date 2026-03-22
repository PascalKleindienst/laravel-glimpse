# Laravel Glimpse

Privacy-first, server-side analytics for Laravel. Zero cookies. No JavaScript. No GDPR consent banners.

## Development Setup

```bash
# Install dependencies
composer install
npm install

# Development server
composer serve

# Build assets
npm run build
npm run watch   # Watch mode

# Code Quality
composer lint          # Full lint (Pint + Rector + Prettier)
composer test          # Full test suite
composer test:unit     # Run tests with coverage
composer test:types    # Run PHPStan
composer test:type-coverage  # Check type coverage (min 100%)
composer test:lint      # Code style checks only
```

## Tech Layers

- **Framework**: Laravel 12/13
- **Language**: PHP 8.4 (strict types, property hooks)
- **Styling**: Tailwind CSS v4 with Vite
- **Testing**: Pest PHP (parallel, coverage min 90%, type coverage min 100%)
- **Code Style**: Laravel Pint (PSR-12 + Laravel preset) + Prettier (Blade/JS)
- **Static Analysis**: PHPStan level 8
- **Livewire**: v3/v4 for dashboard components

## Project Structure

```
src/
├── Contracts/         # Interface definitions
├── Console/Commands/  # Artisan commands
├── Data/              # Data transfer objects
├── Enums/             # PHP enums
├── Events/            # Event classes
├── Exceptions/        # Custom exceptions
├── Facades/           # Laravel facades
├── Http/Middleware/   # Request middleware
├── Jobs/              # Queue jobs
├── Livewire/          # Livewire components
│   ├── Dashboard.php
│   └── Metrics/       # Metric components (Charts, Tables, Breakdowns)
├── Models/            # Eloquent models
├── Resolvers/         # Visitor data resolvers (Geo, Device, Language, Referrer)
├── Services/          # Business logic services
└── Values/            # Value objects

resources/
├── css/               # Tailwind styles
├── js/                # JavaScript
└── views/             # Blade templates
    ├── components/     # Reusable Blade components
    ├── livewire/       # Livewire view templates
    └── layouts/        # Layout templates

config/glimpse.php     # Package configuration
database/              # Migrations
tests/
├── Unit/              # Unit tests
├── Feature/           # Feature/integration tests
└── Pest.php           # Test configuration
```

## Code Standards

### General Rules

- All PHP files MUST have `declare(strict_types=1);`
- All classes MUST be `final` by default (enforced by Pint)
- Use interfaces (Contracts) for all services
- PHPStan level 8 compliance required
- Type coverage must be 100%

### Naming Conventions

- Classes: `PascalCase`
- Interfaces: `PascalCase` with `Contract` suffix (e.g., `QueryServiceContract`)
- Enums: `PascalCase`
- Value Objects: `PascalCase`
- Constants: `SCREAMING_SNAKE_CASE`
- Methods/Properties: `camelCase`

### PHP Style

- Follow Laravel Pint configuration (`pint.json`)
- Property hooks syntax for PHP 8.4+ where appropriate
- PHPDoc blocks for complex return types
- PHPDoc `@property-read` for Livewire computed properties

### File Organization

- Colocate tests with source files (`Dashboard.php` → `DashboardTest.php`)
- Group Livewire components with their view templates
- Use contracts over concrete implementations in type hints

## Important Patterns

### Service Pattern

All services must have a corresponding contract:

```php
// src/Services/QueryService.php
final class QueryService implements QueryServiceContract

// src/Contracts/QueryServiceContract.php
interface QueryServiceContract
```

### Livewire Components

Use `#[Computed]` for cached properties:

```php
#[Computed]
public function dateRange(): DateRange
{
    return DateRange::fromPreset($this->preset);
}
```

### Resolver Pattern

Resolvers extract visitor data from requests:

```php
// Must implement Resolver contract
final class GeoResolver implements Resolver
```

### Event Dispatching

Use `Glimpse::event()` helper for custom events:

```php
Glimpse::event('checkout', ['plan' => 'pro']);
```

## Testing Guidelines

- Pest PHP with parallel execution
- Minimum 90% code coverage required
- Minimum 100% type coverage required
- Feature tests for HTTP interactions, unit tests for logic
- Use `render()` assertions for Livewire component tests
- Mock external services (GeoIP, BrowserDetect)

## Common Pitfalls to Avoid

- DON'T: Create classes without corresponding tests
- DON'T: Use `var_dump()`/`dd()` - use proper logging
- DON'T: Bypass PHPStan level 8 errors
- DON'T: Skip type coverage on new code
- DON'T: Use `final class` without constructor property promotion
- DO: Check existing contracts before creating new services
- DO: Follow class element ordering from pint.json
- DO: Keep methods small and focused
- DO: Use value objects for domain concepts

## Performance Considerations

- Dashboard reads from pre-aggregated data only (never raw tables)
- Tracking adds zero latency (queue-driven via `ProcessVisitJob`)
- Lazy load heavy metric components
- Use chunking for bulk operations (see `BackfillDataCommand`)

## Deployment

- Packagist for distribution (`composer require`)
- Main branch → Packagist release
- GitHub Actions for CI/CD (tests, lint, type coverage)
- Codecov for coverage tracking

## Additional Resources

- Architecture decisions: README.md "How It Works" section
- API documentation: README.md
- Config reference: `config/glimpse.php`
- Livewire components: `src/Livewire/`
