# CrispCMS Changelog

## 17.0.0

!>  [[/upgrade/17.0.md|Migrate from previous version]]

- [BREAKING] - Remove `CRISP_VERSION`, `API_VERSION`, `RELEASE_NAME`, `RELEASE_ICON`, `RELEASE_ART`
- [FEAT] - Add `Build` Class for Version related Functions
- [BREAKING] - Delete `csrf` Twig Filter
- [BREAKING] - Delete `render` Twig Filter
- [BREAKING] - Delete `json` Twig Filter
- [BREAKING] - Delete `APIKey` Class
- [BREAKING] - Delete `LogTypes` Class
- [BREAKING] - Refactor Directory Structure
- [QOL] - CI Security
- [QOL] - Add PHP-CS-Fixer
- [QOL] - Optimize Dockerfile
- [QOL] - Code Cleanup


#### Release Candidates

!> Release Candidates are not recommended to run in production.

- 17.0.0.rc.1
- 17.0.0.rc.2
- 17.0.0.rc.3
- 17.0.0.rc.4
- 17.0.0.rc.5
- 17.0.0.rc.6
- 17.0.0.rc.7


## 16.1.2

- [QOL] Bump Version to 16.1.2

## 16.1.1

- [DOCS] - Update Docs
- [FEAT] - Add more License Options to CLI
- [FEAT] - Add `--no-formatting option` to CLI
- [FEAT] - Removed `VERBOSITY` env and added `LOG_LEVEL`
- [QOL] - Trace all functions in Crisp now
- [QOL] - Add FileLogger


## 16.1.0

- [DEPRECATION] - Deprecate `csrf` Twig Filter
- [DEPRECATION] - Deprecate `render` Twig Filter
- [DEPRECATION] - Deprecate `json` Twig Filter
- [DEPRECATION] - Deprecate `APIKey` Class
- [SECURITY] - Fix Exposed Instance ID in Version endpoint


## 16.0.3

- [QOL] - Use `__METHOD__` instead of `__CLASS__` for logging


## 16.0.2

- [FIX] - Fix missing USE directive for Carbon


## 16.0.1

- [QOL] - New Renderer Initialization
- [BREAKING] - Removed old Logging System, use Monolog PSR-3
