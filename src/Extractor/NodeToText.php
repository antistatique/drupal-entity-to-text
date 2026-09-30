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
   * Construct a new NodeToText object.
   *
   * @param \Drupal\entity_to_text\HtmlPurifier $htmlPurifier
   *   The HTML Purifier service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $fieldTypeManager
   *   The field type manager to define field.
   */
  public function __construct(
    protected HtmlPurifier $htmlPurifier,
    protected RendererInterface $renderer,
    protected FieldTypePluginManagerInterface $fieldTypeManager,
  ) {
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
