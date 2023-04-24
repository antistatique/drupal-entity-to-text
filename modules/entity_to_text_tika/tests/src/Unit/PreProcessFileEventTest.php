<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\entity_to_text_tika\Event\EntityToTextTikaEvents;
use Prophecy\Prophecy\ObjectProphecy;
use Drupal\entity_to_text_tika\Event\PreProcessFileEvent;
use Drupal\entity_to_text_tika\Extractor\FileToText;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\file\Entity\File;
use Psr\Log\LoggerInterface;
use Vaites\ApacheTika\Clients\WebClient;
use Prophecy\Prophet;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\entity_to_text_tika\Event\PreProcessFileEvent
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 */
class PreProcessFileEventTest extends UnitTestCase {
  /**
   * A mocked instance of a Tika client.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Vaites\ApacheTika\Clients\WebClient
   */
  protected $client;

  /**
   * The event dispatcher.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

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
  private $fileToText;

  /**
   * The file used for testing.
   *
   * @var \Drupal\Tests\entity_to_text_tika\Unit\File|\Prophecy\Prophecy\ObjectProphecy
   */
  private ObjectProphecy|File $testFile;

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
    $fileSystem = $this->prophet->prophesize(FileSystemInterface::class);
    $loggerFactory = $this->prophet->prophesize(LoggerChannelFactoryInterface::class);
    $logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->eventDispatcher = $this->prophet->prophesize(EventDispatcherInterface::class);

    $loggerFactory->get('entity_to_text')
      ->willReturn($logger->reveal());

    // Create a test file object.
    $this->testFile = $this->prophet->prophesize(File::class);
    $this->testFile->getFileUri()
      ->willReturn('public://file/test.txt')
      ->shouldBeCalled();

    $fileSystem->realpath('public://file/test.txt')
      ->willReturn('/var/www/web/sites/default/files/file/test.txt')
      ->shouldBeCalled();

    $this->client->getText('/var/www/web/sites/default/files/file/test.txt')
      ->willReturn('Commodo duis lorem vestibulum imperdiet vel hac')
      ->shouldBeCalled();
    $this->client->setOCRLanguage('eng')->shouldBeCalled();

    $this->fileToText = new FileToText($settings, $fileSystem->reveal(), $loggerFactory->reveal(), $this->eventDispatcher->reveal());
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
   * Ensure the PreProcessFileEvent is triggered once on FileToText extractor.
   */
  public function testFileToTextTrigger(): void {
    // The event that must be dispatch.
    $event = new PreProcessFileEvent($this->client->reveal(), $this->testFile->reveal());

    $this->eventDispatcher->dispatch($event, EntityToTextTikaEvents::PRE_PROCESS_FILE)
      ->willReturn($event)
      ->shouldBeCalled();
    $this->fileToText->fromFileToText($this->testFile->reveal());
  }

}
