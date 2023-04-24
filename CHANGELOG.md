# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Changed
- increase timeout to 20sec

### Fixed
- fix D10 deprecations: Creation of dynamic property is deprecated

### Added
- add event PRE_PROCESS_FILE to allow client or file alteration before Tika OCR

## [1.0.0] - 2023-01-27
### Added
- init module
- provides a number of utility and helper APIs for developers to transform content into plain text
- add coverage for Drupal 9.3, 9.4 & 9.5
- drop support of drupal below 9.3.x
- add dependabot for Github Action dependency
- add upgrade-status check
- add official support of drupal 10.0

### Fixed
- fix unworking Paragraph to Text transformer
- fix PHPUnit deprecated prophecy integration

### Removed
- remove satackey/action-docker-layer-caching on Github Actions

[Unreleased]: https://github.com/antistatique/drupal-entity-to-text/compare/1.0.0...HEAD
[1.0.0]: https://github.com/antistatique/drupal-entity-to-text/releases/tag/1.0.0
