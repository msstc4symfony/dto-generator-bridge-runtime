# Changelog

All notable changes to this project are documented in this file. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project adheres to
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.4.0] - 2026-10-28

### Added

- `CollectionNormalizer`, registered by `DtoGeneratorRuntimeBundle`: reads a list into the collection object a
  generated DTO declares (dto-generator's `dto.collections`) and writes it back as the list. The property's Context
  names the class and the Serializer type of its items under `dto_generator_collection`; the class is built through
  `fromArray()` or its constructor and read through `toArray()` or iteration.

## [1.3.0] - 2026-10-15

### Added

- The Validator constraint `ExactlyOneOf`, with its validator, for the JSON Schema keyword `oneOf`: the value passes
  exactly one of its constraints; the violation says how many it passes and, when none, what each said.

## [1.2.0] - 2026-10-14

### Added

- The Validator constraint `Not`, with its validator, for the JSON Schema keyword `not`: a value that passes all of
  its constraints is a violation.

## [1.1.0] - 2026-10-13

### Added

- The Validator constraints `DependentRequired`, `Keys` and `Contains`, with their validators, for the JSON Schema
  keywords `dependentRequired`, `propertyNames` and `contains` (with `minContains` and `maxContains`), which
  `msstc4symfony/dto-generator-bridge` writes. They work on PHP 7.4 and with `symfony/validator` 5.4 to 8.
- The Validator constraints `PatternProperty` for `patternProperties`, and `IfThenElse` with `Property` and `Present`
  for `if`, `then` and `else`, with their validators.

## [1.0.0] - 2026-10-12

### Added

- `AdditionalProperties`, `AdditionalPropertiesNormalizer` and `ClassLayout`, moved from the unreleased
  `additionalProperties: spread` of `msstc4symfony/dto-generator-bridge` under the same names, so the DTOs it
  generates need neither the bridge nor the generator in production.
- `DtoGeneratorRuntimeBundle`, which registers the normalizer in the application's serializer, and `Normalizers`,
  the registration it shares with the bridge's bundle.

[Unreleased]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/compare/v1.4.0...HEAD
[1.4.0]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/msstc4symfony/dto-generator-bridge-runtime/releases/tag/v1.0.0
