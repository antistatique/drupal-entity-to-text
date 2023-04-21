<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\entity_to_text_tika\Event\EntityToTextTikaEvents;
use Drupal\entity_to_text_tika\Event\PreProcessFileEvent;
use Drupal\entity_to_text_tika\Extractor\FileToText;
use Drupal\file\Entity\File;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Vaites\ApacheTika\Clients\WebClient;
use Prophecy\Prophet;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the Tika File Extractor.
 *
 * @coversDefaultClass \Drupal\entity_to_text_tika\Extractor\FileToText
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 *
 * @internal
 */
final class FileToTextTest extends UnitTestCase {

  /**
   * A mocked instance of a Tika client.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Vaites\ApacheTika\Clients\WebClient
   */
  protected $client;

  /**
   * A mocked instance of a filesystem.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A mocked instance of a logger channel factory.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * A mocked instance of a logger.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The prophecy object.
   *
   * @var \Prophecy\Prophet
   */
  private $prophet;

  /**
   * The File To Text extractor.
   *
   * @var \Drupal\entity_to_text_tika\Extractor\FileToText
   */
  private FileToText $fileToText;

  /**
   * The event dispatcher.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->prophet = new Prophet();
    $settings['entity_to_text_tika.connection']['host'] = 'tika';
    $settings['entity_to_text_tika.connection']['port'] = '9998';
    $settings = new Settings($settings);

    $this->client = $this->prophet->prophesize(WebClient::class);
    $this->fileSystem = $this->prophet->prophesize(FileSystemInterface::class);
    $this->loggerFactory = $this->prophet->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->eventDispatcher = $this->prophet->prophesize(EventDispatcherInterface::class);

    $this->loggerFactory->get('entity_to_text')
      ->willReturn($this->logger->reveal());

    $this->fileToText = new FileToText($settings, $this->fileSystem->reveal(), $this->loggerFactory->reveal(), $this->eventDispatcher->reveal());
    $this->fileToText->setClient($this->client->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    $this->prophet->checkPredictions();
  }

  /**
   * @covers ::fromFileToText
   */
  public function testFromFileToTextEmptySettings(): void {
    $settings['entity_to_text_tika.connection']['host'] = NULL;
    $settings['entity_to_text_tika.connection']['port'] = NULL;
    $settings = new Settings($settings);

    $fileToText = new FileToText($settings, $this->fileSystem->reveal(), $this->loggerFactory->reveal(), $this->eventDispatcher->reveal());

    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);

    self::assertEmpty($fileToText->fromFileToText($file->reveal()));
  }

  /**
   * @covers ::fromFileToText
   */
  public function testFromFileToText(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->getFileUri()
      ->willReturn('public://file/test.txt')
      ->shouldBeCalled();

    $this->client->setOCRLanguage('eng')->shouldBeCalled();

    $this->fileSystem->realpath('public://file/test.txt')
      ->willReturn('/var/www/web/sites/default/files/file/test.txt')
      ->shouldBeCalled();

    $this->client->getText('/var/www/web/sites/default/files/file/test.txt')
      ->willReturn('Commodo duis lorem vestibulum imperdiet vel hac')
      ->shouldBeCalled();

    $preprocessFileEvent = new PreProcessFileEvent($this->client->reveal(), $file->reveal());
    $this->eventDispatcher->dispatch($preprocessFileEvent, EntityToTextTikaEvents::PRE_PROCESS_FILE)
      ->shouldBeCalled()
      ->willReturn($preprocessFileEvent);

    self::assertEquals('Commodo duis lorem vestibulum imperdiet vel hac', $this->fileToText->fromFileToText($file->reveal()));
  }

  /**
   * @covers ::fromFileToText
   */
  public function testFromFileToTextException(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->getFileUri()
      ->willReturn('public://file/test.txt')
      ->shouldBeCalled();

    $file->id()
      ->willReturn(123)
      ->shouldBeCalled();

    $this->client->setOCRLanguage('fra')->shouldBeCalled();

    $this->fileSystem->realpath('public://file/test.txt')
      ->willReturn('/var/www/web/sites/default/files/file/test.txt')
      ->shouldBeCalled();

    $this->client->getText('/var/www/web/sites/default/files/file/test.txt')
      ->willThrow(new \Exception('foo bar'))
      ->shouldBeCalled();

    $preprocessFileEvent = new PreProcessFileEvent($this->client->reveal(), $file->reveal());
    $this->eventDispatcher->dispatch($preprocessFileEvent, EntityToTextTikaEvents::PRE_PROCESS_FILE)
      ->shouldBeCalled()
      ->willReturn($preprocessFileEvent);

    $this->logger->notice("Document '@fid' on '@path' can't be processed by Tika. Got: @message.", [
      '@fid' => 123,
      '@path' => '/var/www/web/sites/default/files/file/test.txt',
      '@message' => 'foo bar',
    ])->shouldBeCalled();

    $this->fileToText->fromFileToText($file->reveal(), 'fra');
  }

}
