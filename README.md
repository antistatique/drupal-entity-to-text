# Entity to Text

This suite is primarily a set of APIs and tools to improve the developer experience.

This module provides a number of utility and helper APIs for developers to transform content into plain text.

## Use Entity to Text if

  - You need to get plain-text content of Nodes for Indexing content into a Search Engine (Solr, Elasticsearch, ...).
  - You need to transform "Node entity" field(s) into plain-text content.
  - You need to transform "Paragraphs entity" field(s) into plain-text content.
  - You need to transform "File entity" into plain-text through Tika.

## Dependencies

The main module requires `ezyang/htmlpurifier`

The submodule `entity_to_text_tika` requires the library `vaites/php-apache-tika`.
The submodule `entity_to_text_paragraphs` requires the library `drupal/paragraphs`.

## Supporting organizations

This project is sponsored by [Antistatique](https://www.antistatique.net), a Swiss Web Agency.
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).

## Getting Started

We highly recommend you to install the module using `composer`.

```bash
$ composer require antistatique/drupal-entity-to-text
```

## Examples

### Node fields to text

#### Usage

```php
```

### Paragraphs to text

#### Prerequisite

- Enabled `entity_to_text_tika` module

#### Usage

```php
```

### File to text

#### Prerequisite

- Having access to Tika as a RESTful API via the Tika server.
- Enabled `entity_to_text_tika` module
- Setup the `settings.php` configuration

```php
/**
 * Apache Tika connection.
 */
$settings['entity_to_text_tika.connection']['host'] = 'tika';
$settings['entity_to_text_tika.connection']['port'] = '9998';
```

#### Usage

```php
```
