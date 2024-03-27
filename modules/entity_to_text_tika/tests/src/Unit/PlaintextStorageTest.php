<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\entity_to_text_tika\Storage\PlaintextStorage;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

/**
 * Tests the Plaintext File Storage.
 *
 * @coversDefaultClass \Drupal\entity_to_text_tika\Storage\PlaintextStorage
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 *
 * @internal
 */
final class PlaintextStorageTest extends UnitTestCase {
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
   * @var \Drupal\entity_to_text_tika\Storage\PlaintextStorage
   */
  protected PlaintextStorage $plaintextStorage;

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

    $this->plaintextStorage = new PlaintextStorage($this->fileSystem->reveal(), $this->loggerFactory->reveal(), $this->streamWrapperManager->reveal());
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
   * @covers ::saveTextToFile
   */
  public function testSaveTextToFile(): void {
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

    $this->fileSystem->prepareDirectory('private://entity-to-text/ocr', FileSystemInterface::CREATE_DIRECTORY)
      ->shouldBeCalled();

    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->plaintextStorage->saveTextToFile($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileExists('/tmp/199-test.pdf.en.ocr.txt');
    self::assertEquals('lorem ipsum', file_get_contents('/tmp/199-test.pdf.en.ocr.txt'));
  }

  /**
   * @covers ::saveTextToFile
   */
  public function testSaveTextToFileInvalidScheme(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->fileSystem->prepareDirectory('private://entity-to-text/ocr', FileSystemInterface::CREATE_DIRECTORY)
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $this->fileSystem->realpath(Argument::any())->shouldNotBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The destination path is not a valid stream wrapper');
    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->plaintextStorage->saveTextToFile($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileDoesNotExist('/tmp/199-test.pdf.en.ocr.txt');
  }

  /**
   * @covers ::saveTextToFile
   */
  public function testSaveTextToFileInvalidRealpath(): void {
    // Create a test file object.
    $file = $this->prophet->prophesize(File::class);
    $file->id()
      ->willReturn(199)
      ->shouldBeCalled();

    $file->getFilename()
      ->willReturn('test.pdf')
      ->shouldBeCalled();

    $this->fileSystem->prepareDirectory('private://entity-to-text/ocr', FileSystemInterface::CREATE_DIRECTORY)
      ->shouldBeCalled();

    $this->streamWrapperManager->isValidScheme('private')
      ->willReturn(TRUE)
      ->shouldBeCalled();

    $this->fileSystem->realpath('private://entity-to-text/ocr')
      ->willReturn(FALSE)
      ->shouldBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The resolved realpath from uri "private://entity-to-text/ocr" is not a valid directory.');
    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->plaintextStorage->saveTextToFile($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileDoesNotExist('/tmp/199-test.pdf.en.ocr.txt');
  }

  /**
   * @covers ::loadTextFromFile
   */
  public function testLoadTextFromFileInvalidScheme(): void {
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

    $this->fileSystem->prepareDirectory('private://entity-to-text/ocr', FileSystemInterface::CREATE_DIRECTORY)
      ->shouldBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The destination path is not a valid stream wrapper');
    self::assertEquals('/tmp/320-foo.pdf.en.ocr.txt', $this->plaintextStorage->loadTextFromFile($file->reveal(), 'en'));
    self::assertFileExists('/tmp/320-foo.pdf.en.ocr.txt');
    self::assertEquals('lorem ipsum', file_get_contents('/tmp/320-foo.pdf.en.ocr.txt'));
  }

  /**
   * @covers ::loadTextFromFile
   */
  public function testLoadTextFromFileInvalidRealpath(): void {
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

    $this->fileSystem->prepareDirectory('private://entity-to-text/ocr', FileSystemInterface::CREATE_DIRECTORY)
      ->shouldBeCalled();

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('The resolved realpath from uri "private://entity-to-text/ocr" is not a valid directory.');
    self::assertEquals('/tmp/199-test.pdf.en.ocr.txt', $this->plaintextStorage->saveTextToFile($file->reveal(), 'lorem ipsum', 'en'));
    self::assertFileDoesNotExist('/tmp/199-test.pdf.en.ocr.txt');
  }

}
