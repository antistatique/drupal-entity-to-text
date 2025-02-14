<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\entity_to_text_tika\Storage\LocalFileStorage;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

/**
 * Tests the Plaintext File Storage.
 *
 * @coversDefaultClass \Drupal\entity_to_text_tika\Storage\LocalFileStorage
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 *
 * @internal
 */
final class LocalFileStorageTest extends UnitTestCase {
  use TestFileCreationTrait;

  /**
   * The prophecy object.
   *
   * @var \Prophecy\Prophet
   */
  protected $prophet;

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
   * A mocked instance of a stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $streamWrapperManager;

  /**
   * The plain-text storage processor.
   *
   * @var \Drupal\entity_to_text_tika\Storage\LocalFileStorage
   */
  protected LocalFileStorage $localFileStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->prophet = new Prophet();

    $this->fileSystem = $this->prophet->prophesize(FileSystemInterface::class);
    $this->loggerFactory = $this->prophet->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->streamWrapperManager = $this->prophet->prophesize(StreamWrapperManagerInterface::class);

    $this->loggerFactory->get('entity_to_text_tika')
      ->willReturn($this->logger->reveal())->shouldBeCalled();

    $this->localFileStorage = new LocalFileStorage($this->fileSystem->reveal(), $this->loggerFactory->reveal(), $this->streamWrapperManager->reveal());
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();

    $this->prophet->checkPredictions();
    @unlink('/tmp/199-test.pdf.en.ocr.txt');
  }

  /**
   * @covers ::save
   */
  public function testSave(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(TRUE)
      ->shouldBeCalled();

    $this->fileSystem->realpath('private://entity-to-text/ocr')
      ->willReturn('/tmp')
      ->shouldBeCalled();

    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->localFileStorage->save($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileExists('/tmp/199-test.pdf.en.ocr.txt');
    self::assertEquals('lorem ipsum', file_get_contents('/tmp/199-test.pdf.en.ocr.txt'));
  }

  /**
   * @covers ::save
   */
  public function testSaveInvalidScheme(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $this->fileSystem->realpath(Argument::any())->shouldNotBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The destination path is not a valid stream wrapper');
    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->localFileStorage->save($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileDoesNotExist('/tmp/199-test.pdf.en.ocr.txt');
  }

  /**
   * @covers ::save
   */
  public function testSaveInvalidRealpath(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(TRUE)
      ->shouldBeCalled();

    $this->fileSystem->realpath('private://entity-to-text/ocr')
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The resolved realpath from uri "private://entity-to-text/ocr" is not a valid directory.');
    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->localFileStorage->save($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileDoesNotExist('/tmp/199-test.pdf.en.ocr.txt');
  }

  /**
   * @covers ::load
   */
  public function testloadInvalidScheme(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $this->fileSystem->realpath(Argument::any())->shouldNotBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The destination path is not a valid stream wrapper');
    self::assertEquals('/tmp/320-foo.pdf.en.ocr.txt', $this->localFileStorage->load($file->reveal(), 'en'));
    self::assertFileExists('/tmp/320-foo.pdf.en.ocr.txt');
    self::assertEquals('lorem ipsum', file_get_contents('/tmp/320-foo.pdf.en.ocr.txt'));
  }

  /**
   * @covers ::load
   */
  public function testloadInvalidRealpath(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(TRUE)
      ->shouldBeCalled();

    $this->fileSystem->realpath('private://entity-to-text/ocr')
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The resolved realpath from uri "private://entity-to-text/ocr" is not a valid directory.');
    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->localFileStorage->save($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileDoesNotExist('/tmp/199-test.pdf.en.ocr.txt');
  }

  /**
   * @covers ::prepareStorage
   */
  public function testPrepareStorage(): void {
    self::expectNotToPerformAssertions();
    $this->fileSystem->prepareDirectory('private://entity-to-text/ocr', FileSystemInterface::CREATE_DIRECTORY)
      ->shouldBeCalled();
    $this->localFileStorage->prepareStorage();
  }

}
