# Changelog

All notable changes to `CloudwaysDeployManager` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [1.3.0] - 2025-12-29

### Added

- Add support for Laravel 12
- Add CloudwaysServerRestartCommand to allow restarting a server

## [1.2.3] - 2025-09-05

### Fixed 

- CloudDeployManagerServiceProvider->generateMigrationName
- CloudwaysREST->startGitPullLocal() as the API can return an int or a string

## [1.2.2] - 2025-02-05

### Fixed

- Fix CloudDeployManagerServiceProvider->generateMigrationName() to non-static and protected for the change in spatie/laravel-package-tools 1.18

## [1.2.1] - 2024-10-17

### Fixed

- Fix to correct column names in SSH command #1

## [1.2.0] - 2024-10-16

### Added

- Add config setting: `CW_DM_HTTP_TIMEOUT` to customize HTTP timeout to the Cloudways API
- Add --d|discard option to the `cw:deploy:laravel` command, if 1 then it will set 
`composer config --global discard-changes true`

## [1.1.0] - 2024-10-16

### Added
- Add `--c|columns` option to the `cw:manager:list` command ex: `php artisan cw:manager:list -c server`

## [1.0.0] - 2024-10-05

Initial Release
