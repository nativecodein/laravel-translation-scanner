# Laravel Translation Scanner

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nativecodein/laravel-translation-scanner.svg?style=flat-square)](https://packagist.org/packages/nativecodein/laravel-translation-scanner)
[![Total Downloads](https://img.shields.io/packagist/dt/nativecodein/laravel-translation-scanner.svg?style=flat-square)](https://packagist.org/packages/nativecodein/laravel-translation-scanner)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](LICENSE)

**Laravel Translation Scanner** is a zero-config Laravel package that scans your entire project — **PHP, Blade, and Inertia React (JS / TS / JSX / TSX)** files — and automatically appends every missing translation string to `resources/lang/en.json`.

Stop manually copy-pasting strings into your language files. Run one Artisan command and ship i18n-ready Laravel apps in seconds.

> Works seamlessly with **Laravel + Inertia.js + React**, **Blade**, and pure PHP controllers. Supports Laravel **10, 11, 12, and 13**.

---

## Why Laravel Translation Scanner?

- **Automatic discovery** — finds every `__()`, `trans()`, and `t()` call across your codebase
- **Inertia React aware** — scans `.js`, `.ts`, `.jsx`, and `.tsx` files for `t("...")`, `t(\`...\`)`, and `t(variable)` usage
- **Local-variable resolution** — resolves `const title = "Settings"; t(title)` and adds `Settings`
- **Auto translation support** — generate translated JSON language files instantly
- **Bulk translate support** — translate multiple locales in one command
- **Provider fallback system** — automatically switches between translation providers
- **Rate-limit protection** — built-in delay handling to reduce API blocking
- **Non-destructive** — only appends missing keys; never overwrites your existing translations
- **Sorted output** — keys are alphabetically sorted in `en.json` for clean diffs
- **Smart exclusions** — skips `vendor/`, `node_modules/`, `bootstrap/`, `storage/`, `tests/`, and other noise
- **Zero configuration** — install and run

---

## Installation

Install via [Composer](https://getcomposer.org):

```bash
composer require nativecodein/laravel-translation-scanner
```

The service provider is auto-discovered. No further setup required.

---

# Scan Translations

Run the scanner from your Laravel project root:

```bash
php artisan translations:scan
```

The command will:

1. Read existing `resources/lang/en.json` (or create it if missing).
2. Recursively scan your project for translatable strings.
3. Append any missing keys with the key as the default value.
4. Save the sorted `en.json` back to disk.

---

# Auto Translate Language Files

Generate translated language JSON files automatically:

```bash
php artisan translations:translate ta
```

Example generated file:

```txt
resources/lang/ta.json
```

---

# Bulk Translate

Translate multiple languages in one command:

```bash
php artisan translations:translate ta ar ro
```

Generated:

```txt
resources/lang/ta.json
resources/lang/ar.json
resources/lang/ro.json
```

---

# Translation Providers

The package automatically falls back between:

```txt
Google Translate
LibreTranslate
Lingva Translate
```

If one provider fails, the next provider is used automatically.

---

# Rate Limit Protection

Built-in API protection:

```php
usleep(300000);
```

This helps reduce temporary API blocking from free translation services.

---

## Supported Translation Patterns

### PHP / Blade

```php
{{ __('Dashboard') }}

{{ __('Plugins') }}

return trans('Welcome Back');
```

### Inertia React / JS / TS / JSX / TSX

```tsx
t("Login");

t("Register");

t(`Dashboard`);
```

### Local Variable Resolution

```tsx
const title = "Settings";

t(title);
```

---

## Example Output

After running `php artisan translations:scan`, your `resources/lang/en.json`:

```json
{
  "Dashboard": "Dashboard",
  "Login": "Login",
  "Plugins": "Plugins",
  "Register": "Register",
  "Settings": "Settings",
  "Welcome Back": "Welcome Back"
}
```

Console output:

```txt
Added: Dashboard
Added: Plugins
Added: Welcome Back
Added: Login
Added: Settings
Translation scan completed.
```

---

## Supported File Types

| Extension    | Scans For                               |
| ------------ | --------------------------------------- |
| `.php`       | `__('...')`, `trans('...')`             |
| `.blade.php` | `__('...')`, `trans('...')`             |
| `.js`        | `t('...')`, `t(\`...\`)`, `t(variable)` |
| `.ts`        | `t('...')`, `t(\`...\`)`, `t(variable)` |
| `.jsx`       | `t('...')`, `t(\`...\`)`, `t(variable)` |
| `.tsx`       | `t('...')`, `t(\`...\`)`, `t(variable)` |

---

## Excluded Folders

The scanner automatically skips these directories:

```txt
bootstrap/
config/
database/
routes/
storage/
tests/
vendor/
node_modules/
```

---

## Supported Laravel Versions

| Laravel | Status    |
| ------- | --------- |
| 10.x    | Supported |
| 11.x    | Supported |
| 12.x    | Supported |
| 13.x    | Supported |

---

## Updating

```bash
composer update nativecodein/laravel-translation-scanner
```

Then re-run:

```bash
php artisan translations:scan

php artisan translations:translate ta ar ro
```

---

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

---

## Security

If you discover any security-related issues, please email **oss@nativecode.in** instead of using the public issue tracker.

---

## Support

- Issues: https://github.com/nativecodein/laravel-translation-scanner/issues
- Email: **oss@nativecode.in**

---

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

Copyright © NativeCode.

---

<p align="center">
  Contributed by <a href="https://nativecode.in"><strong>NativeCode</strong></a>
</p>
