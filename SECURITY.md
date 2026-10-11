# Security Policy

## Supported Versions

The package follows semantic versioning. Security fixes are released for the latest
minor on each supported major.

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |
| < 1.0   | :x:                |

Runtime requirements are tracked in `composer.json`:

- PHP >= 7.4 to install beside the bridge (bundle-standard runtime profile `php74`); the normalizer itself needs
  PHP 8.0+
- `symfony/serializer` 5.4, 6.4, 7.x or 8.x

## Reporting a Vulnerability

Please report vulnerabilities privately to maxim.shamaev@gmail.com rather than
opening a public issue. You will get an answer within a week.
