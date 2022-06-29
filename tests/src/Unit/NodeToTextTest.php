<?php

namespace Drupal\Tests\entity_to_text\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\entity_to_text\Extractor\NodeToText;
use Drupal\entity_to_text\HtmlPurifier;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Node to Text Extractor.
 *
 * @coversDefaultClass \Drupal\entity_to_text\Extractor\NodeToText
 *
 * @group entity_to_text
 *
 * @internal
 */
final class NodeToTextTest extends UnitTestCase {

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
   * A mocked instance of the field type manager to define field.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->htmlPurifier = $this->prophesize(HtmlPurifier::class);
    $this->renderer = $this->prophesize(RendererInterface::class);
    $this->fieldTypeManager = $this->prophesize(FieldTypePluginManagerInterface::class);

    $this->nodeToText = new NodetoText($this->htmlPurifier->reveal(), $this->renderer->reveal(), $this->fieldTypeManager->reveal());
  }

  /**
   * @covers ::fromFieldtoText
   */
  public function testFromFieldtoTextWithoutDefinition(): void {
    // Create a test node object.
    $node = $this->prophesize(NodeInterface::class);

    self::assertEmpty($this->nodeToText->fromFieldtoText('field_foo', $node->reveal()));
  }

  /**
   * @covers ::fromFieldtoText
   */
  public function testFromFieldtoTextFieldEmpty(): void {
    $node_field_type_definition = ['default_formatter' => 'default'];
    $node_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $node_field_definition->getType()
      ->willReturn('node')
      ->shouldBeCalled();

    // Create a test node object.
    $node = $this->prophesize(NodeInterface::class);
    $node
      ->getFieldDefinition('field_foo')
      ->willReturn($node_field_definition->reveal())
      ->shouldBeCalled();

    $this->fieldTypeManager
      ->getDefinition('node')
      ->willReturn($node_field_type_definition)
      ->shouldBeCalled();

    $field = $this->prophesize(FieldItemList::class);
    $node->get('field_foo')
      ->willReturn($field->reveal())
      ->shouldBeCalled();

    $field->isEmpty()
      ->willReturn(TRUE)
      ->shouldBeCalled();

    self::assertEmpty($this->nodeToText->fromFieldtoText('field_foo', $node->reveal()));
  }

  /**
   * @covers ::fromFieldtoText
   */
  public function testFromFieldtoText(): void {
    $node_field_type_definition = ['default_formatter' => 'default'];
    $node_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $node_field_definition->getType()
      ->willReturn('node')
      ->shouldBeCalled();

    // Create a test node object.
    $node = $this->prophesize(NodeInterface::class);
    $node
      ->getFieldDefinition('field_foo')
      ->willReturn($node_field_definition->reveal())
      ->shouldBeCalled();

    $this->fieldTypeManager
      ->getDefinition('node')
      ->willReturn($node_field_type_definition)
      ->shouldBeCalled();

    $field = $this->prophesize(FieldItemList::class);
    $node->get('field_foo')
      ->willReturn($field->reveal())
      ->shouldBeCalled();

    $field->isEmpty()
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $render = [];
    $field->view(['label' => 'hidden', 'type' => 'default'])
      ->willReturn($render)
      ->shouldBeCalled();

    $markup_html = ' Quisque dolor vehicula egestas morbi commodo diam   . ';
    $this->renderer
      ->renderRoot($render)
      ->willReturn($markup_html)
      ->shouldBeCalled();

    $htmlPurifier = $this->prophesize(\HtmlPurifier::class);
    $this->htmlPurifier->init()
      ->willReturn($htmlPurifier->reveal())
      ->shouldBeCalled();

    $htmlPurifier->purify($markup_html)
      ->willReturn($markup_html)
      ->shouldBeCalled();

    self::assertEquals('Quisque dolor vehicula egestas morbi commodo diam   .',
      $this->nodeToText->fromFieldtoText('field_foo', $node->reveal()));
  }

  /**
   * @covers ::fromFieldtoText
   */
  public function testFromFieldtoTextMarkupObject(): void {
    $node_field_type_definition = ['default_formatter' => 'default'];
    $node_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $node_field_definition->getType()
      ->willReturn('node')
      ->shouldBeCalled();

    // Create a test node object.
    $node = $this->prophesize(NodeInterface::class);
    $node
      ->getFieldDefinition('field_foo')
      ->willReturn($node_field_definition->reveal())
      ->shouldBeCalled();

    $this->fieldTypeManager
      ->getDefinition('node')
      ->willReturn($node_field_type_definition)
      ->shouldBeCalled();

    $field = $this->prophesize(FieldItemList::class);
    $node->get('field_foo')
      ->willReturn($field->reveal())
      ->shouldBeCalled();

    $field->isEmpty()
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $render = [];
    $field->view(['label' => 'hidden', 'type' => 'default'])
      ->willReturn($render)
      ->shouldBeCalled();

    $markup = Markup::create('  Quisque dolor vehicula egestas morbi commodo diam   . ');
    $this->renderer
      ->renderRoot($render)
      ->willReturn($markup)
      ->shouldBeCalled();

    $htmlPurifier = $this->prophesize(\HTMLPurifier::class);
    $this->htmlPurifier->init()
      ->willReturn($htmlPurifier->reveal())
      ->shouldBeCalled();

    $htmlPurifier->purify($markup->__toString())
      ->willReturn('Quisque dolor vehicula egestas morbi commodo diam   .')
      ->shouldBeCalled();

    self::assertEquals('Quisque dolor vehicula egestas morbi commodo diam   .',
      $this->nodeToText->fromFieldtoText('field_foo', $node->reveal()));
  }

}
