# Changelog

All notable changes to this project are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- The Validator constraints `DependentRequired`, `Keys` and `Contains`, with their validators, for the JSON Schema
  keywords `dependentRequired`, `propertyNames` and `contains` (with `minContains` and `maxContains`), which
  `msstc4symfony/dto-generator-bridge` writes. They work on PHP 7.4 and with `symfony/validator` 5.4 to 8.

## [1.0.0] - 2026-10-12

### Added

- `AdditionalProperties`, `AdditionalPropertiesNormalizer` and `ClassLayout`, moved from the unreleased
  `additionalProperties: spread` of `msstc4symfony/dto-generator-bridge` under the same names, so the DTOs it
  generates need neither the bridge nor the generator in production.
- `DtoGeneratorRuntimeBundle`, which registers the normalizer in the application's serializer, and `Normalizers`,
  the registration it shares with the bridge's bundle.

[Unreleased]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/releases/tag/v1.0.0
