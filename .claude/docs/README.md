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

## Constraints (1.1, 2026-10-12 UTC)

- `src/Constraints`: `DependentRequired`, `Property`, `Present` (класс), `Keys`, `Contains`, `PatternProperty`, `IfThenElse` (`Composite`) + валидаторы; `PropertyKey` (чтение свойств через `(array)`) и `Conjunction` (ветка `IfThenElse`: до Symfony 7.3 `getCompositeOption()` — одна строка) — `@internal`; PHP 7.4 + Symfony 5.4–8. В `phpstan.dist.neon` исключены (baseline `excludePaths`), анализируются `phpstan-symfony.neon`; `Attribute` разрешён в `src/Constraints/*` (`phpstan-php74.neon`).
- Ловушки Rector (шаблон стандарта, менять нельзя): параметр `$payload` получил бы нативный `mixed` (ParamTypeByParentCallTypeRector) — конструкторы без `payload`; `protected static $errorNames` → `private static array` (ломает 5.4) — его нет, имена ошибок только с 6.1. `@param mixed $value` у `validate()` Rector удаляет, PHPStan требует тип — используется `@phpstan-param mixed`. Строки FQCN Rector превращает в `::class` — имена загрузчиков валидатора в тестах собираются конкатенацией (`Validates::loader()`).
- `ContainsValidator` пишет нарушение в контекст, взятый в начале `validate()`: на 5.4 вложенный `Contains` переинициализирует общий экземпляр валидатора (на 7.4+ контекст восстанавливает `validateInContext`) — восстановление `$this->context` было бы эквивалентным мутантом на 8.
- `DependentRequiredValidator` читает `(array)`-приведение (без `setAccessible`, устаревшего в 8.5): точные ключи `\0Class\0name` по иерархии, `\0*\0name`, `name`; неинициализированное (есть `property_exists`) — не задано.
- Сообщения с `word:` в начале переводчик Symfony принимает за метку plural-формы — в тестах сообщения без двоеточия после первого слова.
- `AnnotationsTest` — Doctrine `AnnotationLoader` (Symfony < 7); CI-задание PHP 7.4 ставит `doctrine/annotations:^1.13` и гоняет `tests/Unit/Constraints`.
- CI-уроки (2026-10-12 UTC): тесты `tests/Unit/Constraints` пропускаются без `symfony/validator` (задание «without optional libraries», `Validates::setUp()`); Symfony 5.4 на PHP 8.5 вызывает `ReflectionProperty::setAccessible()` в `PropertyMetadata` — тесты с метаданными валидируют через `Validates::validateMapped()`, пропуская устаревания из `vendor/`; ячейка lowest брала `symfony/translation-contracts` 2.5.0 с неявным nullable (fatal на PHP 8.4) — в `composer-ci.json` `^2.5.3 || ^3.5`.
- Symfony 8 (`ClassMetadata::checkConstraint()`) требует, чтобы всё внутри классового `Composite` было классовым (обход `getNestedConstraints()`): `Property` возвращает оттуда `[]` — его вложенные проверяют значение свойства. Группы до вложенных доходят через `addImplicitGroupName()`, который читает опцию композита напрямую.
- `IfThenElse` без групп берёт группы вложенных и требует, чтобы каждый вложенный был во всех: иначе `if` выполнялся бы по части схемы. Валидатор, как `Contains`, берёт контекст в начале `validate()`.
- Матрица: `scratchpad/runtime-matrix.sh <symfony> <php-image>`, задание PHP 7.4 — `scratchpad/runtime-php74.sh` (`composer require` validator 5.4 + doctrine/annotations, как CI).
