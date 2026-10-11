# Changelog

All notable changes to this project are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `AdditionalProperties`, `AdditionalPropertiesNormalizer` and `ClassLayout`, moved from
  `msstc4symfony/dto-generator-bridge` 1.1 under the same names, so DTOs generated with
  `additionalProperties: spread` need neither the bridge nor the generator in production.
- `DtoGeneratorRuntimeBundle`, which registers the normalizer in the application's serializer, and `Normalizers`,
  the registration it shares with the bridge's bundle.
