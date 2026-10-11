# CLAUDE.md

Guidance for Claude Code working in this repository. Deep references live
under `.claude/docs/`; this file stays lean.

## What this is

The runtime of `msstc4symfony/dto-generator-bridge`: what DTOs generated with
`extensionConfig.symfony.additionalProperties: spread` need in a Symfony application —
the `AdditionalProperties` attribute, `AdditionalPropertiesNormalizer` and
`DtoGeneratorRuntimeBundle`. It lets the generator and the bridge stay `require-dev`.
Spec: `../dto-generator-bridge-symfony/docs/internal/specs/2026-10-11-runtime-and-build-mode-design.md`.

The namespace `Msstc4Symfony\DtoGeneratorBridge\Runtime\` is the bridge's old one on
purpose: generated code names the attribute, so it must not change. The package must
parse on **PHP 7.4** (the bridge requires it and runs there; bundle-standard runtime
profile `php74`), while the normalizer and the bundle's service need PHP 8.0+: no PHP 8
syntax — hence `mixed` in docblocks, single-line `#[...]`, `class_exists(WeakMap::class)`.

## Common commands

All in `Makefile` (shared bundle-standard template):

- `make install-ci` (or `COMPOSER=composer-ci.json composer install`).
- `make check`, `make fix`, `make test`, `make infection`.
- `vendor/bin/phpstan analyse -c phpstan-symfony.neon` and `-c phpstan-php74.neon` — what
  `phpstan.dist.neon` (phpVersion 70400) cannot read; CI runs both ("Runtime checks").

## Conventions

- Code comments in English, only the non-obvious "why".
- Every change: tests first, `make check`, `make test`, `make infection` (MSI 100 %).
- User-facing behaviour changes go into `CHANGELOG.md` (Unreleased) and `README.md`.
- The bridge registers the normalizer through `Normalizers::register()` too: keep it the
  single definition.
