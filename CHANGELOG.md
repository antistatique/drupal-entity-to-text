# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Removed
- disable running on Drupal 12 until Paragraph (root dependencies) is compliant

## [1.3.0] - 2025-11-07
### Added
- add official support of drupal 11.2
- add early support of drupal 12.x

### Removed
- drop support of drupal below 11.x

## [1.2.0] - 2025-08-11
### Added
- add official support of drupal 10.5
- add official support of drupal 11.2

### Removed
- drop support of drupal below 10.4.x
- drop support of drupal below 10.3.x
- drop support of drupal 9.x

## [1.1.0] - 2025-02-25
### Removed
- drop support of drupal below 9.5.x

### Changed
- increase timeout to 20sec

### Fixed
- fix D10 deprecations: Creation of dynamic property is deprecated
- fix phpstan not finding coverage class fromParagraphToText

### Added
- add event PRE_PROCESS_FILE to allow client or file alteration before Tika OCR
- add Drupal GitlabCI
- add cpsell project words for Gitlab-CI
- add a new layer of performance by allowing developers to cache OCR'ed files
- add command entity_to_text:tika:warmup
- add cpsell project words for Gitlab-CI
- add official support of drupal 11.1

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

[Unreleased]: https://github.com/antistatique/drupal-entity-to-text/compare/1.3.0...HEAD
[1.3.0]: https://github.com/antistatique/drupal-entity-to-text/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/antistatique/drupal-entity-to-text/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/antistatique/drupal-entity-to-text/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/antistatique/drupal-entity-to-text/releases/tag/1.0.0
