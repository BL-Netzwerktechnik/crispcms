# CrispCMS Changelog

## 16.1.0

- [DEPRECATION] - Deprecate `csrf` Twig Filter
- [DEPRECATION] - Deprecate `render` Twig Filter
- [DEPRECATION] - Deprecate `json` Twig Filter
- [DEPRECATION] - Deprecate `APIKey` Class
- [SECURITY] - Fix Exposed Instance ID in Version endpoint
- [DOCS] - Update Docs
- [FEAT] - Add more License Options to CLI
- [FEAT] - Add `--no-formatting option` to CLI
- [FEAT] - Removed `VERBOSITY` env and added `LOG_LEVEL`
- [QOL] - Trace all functions in Crisp now
- [QOL] - Add FileLogger

## 16.0.3

- [QOL] - Use `__METHOD__` instead of `__CLASS__` for logging


## 16.0.2

- [FIX] - Fix missing USE directive for Carbon


## 16.0.1

- [QOL] - New Renderer Initialization
- [BREAKING] - Removed old Logging System, use Monolog PSR-3
