# Entity to Text

This suite is primarily a set of APIs and tools to improve the developer experience.

This module provides a number of utility and helper APIs for developers to transform content into plain text.

## Use Entity to Text if

  - You need to get plain-text content of Nodes for Indexing content into a Search Engine (Solr, Elasticsearch, ...).
  - You want to get plain-text of Nodes Paragraphs for SEO or JSON-LD.
  - You need to transform "Node entity" field(s) into plain-text content.
  - You need to transform "Paragraphs entity" field(s) into plain-text content.
  - You need to transform "File entity" into plain-text through Tika.

## Dependencies

The main module requires `ezyang/htmlpurifier`

The submodule `entity_to_text_tika` requires the library `vaites/php-apache-tika`.
The submodule `entity_to_text_paragraphs` requires the library `drupal/paragraphs`.

### Which version should I use?

| Drupal Core | Entity to Text |
|:-----------:|:--------------:|
|     8.x     |       -        |
|     9.x     |     1.0.x      |
|    10.x     |     1.0.x      |

## Getting Started

We highly recommend you to install the module using `composer`.

```yaml
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/antistatique/drupal-entity-to-text"
  }
],
```

```bash
$ composer require drupal/entity_to_text
```

## Examples

### Node fields to text

#### Usage

```php
/** @var string $field_body_content */
$field_body_content = \Drupal::service('entity_to_text.extractor.node_to_text')->fromFieldtoText('body', $node);
/** @var string $field_foo_content */
$field_foo_content = \Drupal::service('entity_to_text.extractor.node_to_text')->fromFieldtoText('field_foo', $node);
```

### Paragraphs to text

#### Prerequisite

- Enabled `entity_to_text_paragraphs` module

#### Usage

```php
/** @var array[] $bodies */
$bodies = \Drupal::service('entity_to_text_paragraphs.extractor.paragraphs_to_text')->fromParagraphToText($node->field_paragraphs);
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
/** @var \Drupal\file\Entity\File $file */
$file = $file_item->entity;
$body = \Drupal::service('entity_to_text_tika.extractor.file_to_text')->fromFileToText($file, 'eng+fra');
```

## Supporting organizations

This project is sponsored by [Antistatique](https://www.antistatique.net), a Swiss Web Agency.
Visit us at [www.antistatique.net](https://www.antistatique.net) or
[Contact us](mailto:info@antistatique.net).

## Credits

Entity to Text is currently maintained by [Kevin Wenger](https://github.com/wengerk). Thank you to all our wonderful [contributors](https://github.com/antistatique/drupal-entity-to-text/contributors) too.

