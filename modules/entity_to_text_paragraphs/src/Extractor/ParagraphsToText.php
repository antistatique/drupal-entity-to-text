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
   * Construct a new ParagraphsToText object.
   *
   * @param \Drupal\entity_to_text\HtmlPurifier $htmlPurifier
   *   The HTML Purifier service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected HtmlPurifier $htmlPurifier,
    protected RendererInterface $renderer,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
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
