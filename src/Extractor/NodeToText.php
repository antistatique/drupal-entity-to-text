<?php

namespace Drupal\entity_to_text\Extractor;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_to_text\HtmlPurifier;
use Drupal\node\NodeInterface;

/**
 * Provide Capabilities to transform a Node content to plain-text strings.
 */
class NodeToText {

  /**
   * The HTML Purifier service.
   *
   * @var \Drupal\entity_to_text\HtmlPurifier
   */
  protected $htmlPurifier;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The field type manager to define field.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Construct a new NodeToText object.
   */
  public function __construct(HtmlPurifier $html_purifier, RendererInterface $renderer, FieldTypePluginManagerInterface $field_type_manager) {
    $this->htmlPurifier = $html_purifier;
    $this->renderer = $renderer;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * Transform a Field into plain text value.
   *
   * @param string $field_name
   *   The field name to fetch from the given node.
   * @param \Drupal\node\NodeInterface $node
   *   The node with the according field to transform.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @return string
   *   The transformed field into a plain text value.
   */
  public function fromFieldtoText(string $field_name, NodeInterface $node): string {
    $field_definition = $node->getFieldDefinition($field_name);
    $field_type_definition = $field_definition ? $this->fieldTypeManager->getDefinition($field_definition->getType()) : NULL;

    if (!$field_type_definition) {
      return '';
    }

    $display_options = ['label' => 'hidden'];
    $display_options['type'] = $field_type_definition['default_formatter'];

    $field = $node->get($field_name);

    if ($field->isEmpty()) {
      return '';
    }

    $view = $field->view($display_options);

    /** @var \Drupal\Core\Render\Markup|string $markup */
    $markup = $this->renderer->renderRoot($view);

    $purifier = $this->htmlPurifier->init();

    if ($markup instanceof Markup) {
      $clean_html = $purifier->purify($markup->__toString());
    }
    else {
      $clean_html = $purifier->purify($markup);
    }

    return trim($clean_html);
  }

}
