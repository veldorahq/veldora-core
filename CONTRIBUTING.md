# Contributing to Veldora Framework Core

Thank you for your interest in contributing to Veldora Framework Core!

## Development Setup

1. Fork and clone the repository:
   ```bash
   git clone https://github.com/veldorahq/veldora-core.git
   cd veldora-core
   ```

2. Install Composer dependencies:
   ```bash
   composer install
   ```

3. Run the test suite:
   ```bash
   composer test
   ```

## Pull Request Guidelines

1. **Branch Naming**: Use descriptive branch names like `feat/new-query-builder-method` or `fix/route-matching-regex`.
2. **Code Standards**: Follow PSR-12 code standards and ensure strict typing (`declare(strict_types=1);`).
3. **Tests**: Add unit tests for any new features or bug fixes.
4. **Commit Messages**: Write clear, conventional commit messages (`feat: ...`, `fix: ...`, `docs: ...`).
