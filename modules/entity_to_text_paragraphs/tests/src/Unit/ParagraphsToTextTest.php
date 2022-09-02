<?php

namespace Drupal\Tests\entity_to_text_paragraphs\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\entity_to_text\HtmlPurifier;
use Drupal\entity_to_text_paragraphs\Extractor\ParagraphsToText;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Paragraphs to Text Extractor.
 *
 * @coversDefaultClass \Drupal\entity_to_text_paragraphs\Extractor\ParagraphsToText
 *
 * @group entity_to_text
 * @group entity_to_text_paragraphs
 *
 * @internal
 */
final class ParagraphsToTextTest extends UnitTestCase {

  /**
   * A mocked instance of the HTML Purifier service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\entity_to_text\HtmlPurifier
   */
  protected $htmlPurifier;

  /**
   * A mocked instance of the renderer service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->htmlPurifier = $this->prophesize(HtmlPurifier::class);
    $this->renderer = $this->prophesize(RendererInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    $this->paragraphsToText = new ParagraphsToText($this->htmlPurifier->reveal(), $this->renderer->reveal(), $this->entityTypeManager->reveal());
  }

  /**
   * @covers ::fromParagraphToText()
   */
  public function testFromParagraphToTextEmpty(): void {
    // Create an empty test Paragraphs collection object list.
    $entityReferences = $this->prophesize(EntityReferenceRevisionsFieldItemList::class);
    $entityReferences->getIterator()->willReturn(new \ArrayIterator([]))->shouldBeCalled();

    self::assertEquals([], $this->paragraphsToText->fromParagraphToText($entityReferences->reveal()));
  }

  /**
   * @covers ::fromParagraphToText()
   */
  public function testFromParagraphToText(): void {
    $paragraph1 = $this->prophesize(Paragraph::class);
    $paragraph1->getEntityTypeId()->willReturn('foo')->shouldBeCalled();
    $paragraph2 = $this->prophesize(Paragraph::class);
    $paragraph2->getEntityTypeId()->willReturn('bar')->shouldBeCalled();

    $fieldRevision1 = new class($paragraph1->reveal()) {
      /**
       * The related paragraph entity.
       *
       * @var \Drupal\paragraphs\Entity\Paragraph
       */
      public $entity;

      /**
       * Construct a new anonymous mimic EntityReferenceRevisionsItem.
       */
      public function __construct($entity) {
        $this->entity = $entity;
      }

      /**
       * Mock of EntityReferenceRevisionsItem::getLangcode.
       */
      public function getLangcode(): string {
        return 'en';
      }

    };

    $fieldRevision2 = new class($paragraph2->reveal()) {
      /**
       * The related paragraph entity.
       *
       * @var \Drupal\paragraphs\Entity\Paragraph
       */
      public $entity;

      /**
       * Construct a new anonymous mimic EntityReferenceRevisionsItem.
       */
      public function __construct($entity) {
        $this->entity = $entity;
      }

      /**
       * Construct a new anonymous mimic EntityReferenceRevisionsItem.
       */
      public function getLangcode(): string {
        return 'en';
      }

    };

    // Create a test Paragraphs collection object list.
    $entityReferences = $this->prophesize(EntityReferenceRevisionsFieldItemList::class);
    $entityReferences->getIterator()
      ->willReturn(new \ArrayIterator([
        $fieldRevision1,
        $fieldRevision2,
      ]))
      ->shouldBeCalled();

    $view_builder_interface = $this->prophesize(EntityViewBuilderInterface::class);
    $this->entityTypeManager->getViewBuilder('foo')
      ->willReturn($view_builder_interface->reveal())
      ->shouldBeCalled();
    $this->entityTypeManager->getViewBuilder('bar')
      ->willReturn($view_builder_interface->reveal())
      ->shouldBeCalled();

    $render1 = ['markup' => 'paragraph1'];
    $view_builder_interface
      ->view($paragraph1, 'full', 'en')
      ->willReturn($render1)
      ->shouldBeCalled();

    $render2 = ['markup' => 'paragraph2'];
    $view_builder_interface
      ->view($paragraph2, 'full', 'en')
      ->willReturn($render2)
      ->shouldBeCalled();

    $markup1 = Markup::create('  Quisque dolor vehicula egestas morbi commodo diam   . ');
    $this->renderer
      ->renderRoot($render1)
      ->willReturn($markup1)
      ->shouldBeCalled();

    $markup2 = Markup::create('Facilisi risus. ');
    $this->renderer
      ->renderRoot($render2)
      ->willReturn($markup2)
      ->shouldBeCalled();

    $htmlPurifier = $this->prophesize(\HTMLPurifier::class);

    $this->htmlPurifier->init()
      ->willReturn($htmlPurifier)
      ->shouldBeCalled();

    $htmlPurifier->purify('  Quisque dolor vehicula egestas morbi commodo diam   . ')
      ->willReturn(' Quisque dolor vehicula egestas morbi commodo diam   . ')
      ->shouldBeCalled();

    $htmlPurifier->purify('Facilisi risus. ')
      ->willReturn('Facilisi risus. ')
      ->shouldBeCalled();

    self::assertEquals([
      'Quisque dolor vehicula egestas morbi commodo diam   .',
      'Facilisi risus.',
    ], $this->paragraphsToText->fromParagraphToText($entityReferences->reveal()));

  }

}
