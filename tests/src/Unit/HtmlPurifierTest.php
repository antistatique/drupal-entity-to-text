<?php

namespace Drupal\Tests\entity_to_text\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\entity_to_text\HtmlPurifier;
use Drupal\Tests\UnitTestCase;

/**
 * Tests HTML Purifier.
 *
 * @coversDefaultClass \Drupal\entity_to_text\HtmlPurifier
 *
 * @group entity_to_text
 *
 * @internal
 */
final class HtmlPurifierTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstructor(): void {
    $file_system = $this->createMock(FileSystemInterface::class);

    $purifier = new HtmlPurifier($file_system);
    self::assertInstanceOf(HtmlPurifier::class, $purifier);
  }

  /**
   * @covers ::init
   */
  public function testInit(): void {
    $file_system = $this->createMock(FileSystemInterface::class);

    $html_purifier = new HtmlPurifier($file_system);
    self::assertInstanceOf(\HTMLPurifier::class, $html_purifier->init());

    // Initializing HTML Purifier should use the default config.
    $purifier = $html_purifier->init();
    self::assertEquals('/HtmlPurifier', $purifier->config->get('Cache.SerializerPath'));
    self::assertTrue($purifier->config->get('AutoFormat.RemoveEmpty'));
    self::assertEquals([], $purifier->config->get('HTML.AllowedElements'));
    self::assertEquals([], $purifier->config->get('CSS.AllowedProperties'));

    // Initializing HTML Purifier with custom config should be applied.
    $config = \HTMLPurifier_Config::createDefault();
    $config->set('Cache.SerializerPath', '/foo');

    $purifier = $html_purifier->init($config);
    self::assertEquals('/foo', $purifier->config->get('Cache.SerializerPath'));
  }

  /**
   * @covers ::getHtmlPurifierConfig
   */
  public function testGetHtmlPurifierConfig(): void {
    $file_system = $this->createMock(FileSystemInterface::class);

    $purifier = new HtmlPurifier($file_system);
    self::assertInstanceOf(\HTMLPurifier_Config::class, $purifier->getHtmlPurifierConfig());

    self::assertEquals('/HtmlPurifier', $purifier->getHtmlPurifierConfig()->get('Cache.SerializerPath'));
    self::assertTrue($purifier->getHtmlPurifierConfig()->get('AutoFormat.RemoveEmpty'));
    self::assertEquals([], $purifier->getHtmlPurifierConfig()->get('HTML.AllowedElements'));
    self::assertEquals([], $purifier->getHtmlPurifierConfig()->get('CSS.AllowedProperties'));
  }

}
