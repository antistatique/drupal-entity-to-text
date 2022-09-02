<?php

namespace Drupal\entity_to_text_paragraphs\Extractor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\entity_to_text\HtmlPurifier;

/**
 * Provide Capabilities to transform a Paragraph content to plain-text strings.
 */
class ParagraphsToText {

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Construct a new ParagraphsToText object.
   */
  public function __construct(HtmlPurifier $html_purifier, RendererInterface $renderer, EntityTypeManagerInterface $entity_type_manager) {
    $this->htmlPurifier = $html_purifier;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Transform Paragraphs into an array of plain text value.
   *
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $paragraph_items
   *   Paragraphs to transform.
   *
   * @return string[]
   *   The transformed paragraphs into an array of plain text.
   */
  public function fromParagraphToText(EntityReferenceRevisionsFieldItemList $paragraph_items): array {
    $values = [];
    /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $paragraph_item */
    foreach ($paragraph_items as $paragraph_item) {
      /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
      $paragraph = $paragraph_item->entity;

      $render_controller = $this->entityTypeManager->getViewBuilder($paragraph->getEntityTypeId());
      $view = $render_controller->view($paragraph, 'full', $paragraph_item->getLangcode());

      /** @var \Drupal\Core\Render\Markup $markup */
      $markup = $this->renderer->renderRoot($view);

      $purifier = $this->htmlPurifier->init();

      $clean_html = $purifier->purify($markup->__toString());
      $values[] = trim($clean_html);
    }

    return $values;
  }

}
