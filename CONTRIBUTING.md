# Contributing

Thank you for your interest in contributing!

## Development Setup

1. Clone the repository
2. Run `composer install` to install dependencies
3. Run `composer test:unit` to execute the test suite

## Available Scripts

| Command                       | Description                                      |
| ----------------------------- | ------------------------------------------------ |
| `composer test`               | Run all tests (lint, types, type-coverage, unit) |
| `composer test:unit`          | Run unit tests                                   |
| `composer test:lint`          | Check code style (Pint, Rector)                  |
| `composer test:types`         | Run static analysis (PHPStan)                    |
| `composer test:type-coverage` | Check type coverage                              |

## Branch Strategy

1. Create a feature branch from `main`
2. Make your changes
3. Open a pull request targeting `main`
4. Ensure all checks pass
5. Request review
6. Squash and merge

## Code Style

This project uses Laravel Pint for code formatting. Run `composer test:lint` before committing to ensure your code follows the style guidelines.

## Testing

All tests must pass before merging. Run `composer test:unit` locally to verify your changes don't break existing functionality.
