# Заметки по dto-generator-bridge-runtime

Создан 2026-10-11 (UTC) выносом `src/Runtime` из моста (спецификация — в мосте,
`docs/internal/specs/2026-10-11-runtime-and-build-mode-design.md`, §2).

## Устройство

- `src/` (слой Runtime): `AdditionalProperties`, `AdditionalPropertiesNormalizer`, `ClassLayout` — без изменений
  из `src/Runtime` моста (в релиз моста не попадали: `spread` вышел бы в 1.2). Namespace `Msstc4Symfony\DtoGeneratorBridge\Runtime\` не менять: его FQCN пишет
  сгенерированный код.
- `src/Bundle` (слой Bundle, зависит от Runtime): `DtoGeneratorRuntimeBundle` → `DtoGeneratorRuntimeExtension`
  (алиас `dto_generator_runtime`, пустая `Configuration` — любой ключ ошибка) → `Normalizers::register()`;
  `DropOrphanNormalizerPass` — приоритет 1 в BEFORE_OPTIMIZATION, т. е. перед `SerializerPass` (0).
- Мост 1.2+ вызывает тот же `Normalizers::register()` и добавляет тот же pass: оба бандла вместе дают одно
  определение, второй pass ничего не делает.

## Подводные камни

- `excludePaths` — в `phpstan-baseline.neon`: `phpstan.dist.neon` стандарт требует побайтно. После `phpstan -b`
  вернуть исключения руками.
- Конфликта с мостом нет: ни один релиз моста не содержал `src/Runtime` (1.1.0 — без `spread`).
- Нормалайзер грузится только на PHP 8.0+ (нативный `mixed`); на 7.4 пакет ставится рядом с мостом, и
  `Normalizers` ничего не регистрирует (`class_exists(WeakMap::class)`). Ветку проверяет задание CI
  «Bundle on PHP 7.4» (Symfony 5.4) — стандартная матрица идёт только на PHP 8.
- Тесты бандла с ядром Symfony — `tests/Integration` (`@coversNothing`: загрузка ядра дольше таймаута Infection);
  те же места покрыты юнит-тестами на голом `ContainerBuilder`.
- `bundle-standard` требует `extra.symfony.require` (значение не проверяет): `^5.4|^6.4|^7.0|^8.0`.
