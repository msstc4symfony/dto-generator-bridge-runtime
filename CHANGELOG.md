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

### Upgrading from the bridge 1.1

The bridge 1.2 requires this package and no longer ships `src/Runtime`; the classes keep their names, so generated
DTOs and code that builds the normalizer need no change. The package conflicts with the bridge before 1.2, which
declares the same classes. To keep the bridge in `require-dev`, register `DtoGeneratorRuntimeBundle` for all
environments.
