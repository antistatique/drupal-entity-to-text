<?php

namespace Drupal\Tests\entity_to_text_tika\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\entity_to_text_tika\Storage\PlaintextStorage;
use Drupal\file\Entity\File;
use Drupal\KernelTests\Core\File\FileTestBase;

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
final class PlaintextStorageTest extends FileTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'entity_to_text_tika', 'user'];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

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

    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);

    $this->fileSystem = $this->container->get('file_system');
    $this->plaintextStorage = $this->container->get('entity_to_text_tika.storage.plain_text');

    $destination = PlaintextStorage::DESTINATION;
    $this->fileSystem->prepareDirectory($destination, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
  }

  /**
   * @covers ::loadTextFromFile
   */
  public function testLoadTextFromFile(): void {
    // Create an OCR file for testing.
    $file_uri = $this->createUri('390-foo.txt.en.ocr.txt', 'Ipsum excepteur id cupidatat commodo', 'private');
    $this->fileSystem->move($file_uri, 'private://entity-to-text/ocr/390-foo.txt.en.ocr.txt', FileSystemInterface::EXISTS_REPLACE);

    // Create a file that correspond to the previous OCR file.
    $file = File::create([
      'uri' => 'public://foo.txt',
      'name' => 'foo',
    ]);
    $file->set('fid', 390);

    self::assertEquals('Ipsum excepteur id cupidatat commodo', $this->plaintextStorage->loadTextFromFile($file, 'en'));
  }

  /**
   * @covers ::loadTextFromFile
   */
  public function testLoadTextFromFileWhenOcrFileNotExists(): void {
    // Create a file that has not been already processed and therefore does not have
    // an OCR associated file.
    $file = File::create([
      'uri' => 'public://foo.txt',
      'name' => 'foo',
    ]);
    $file->set('fid', 380);

    // When the OCR file does not exists, then nothing can be retreived.
    self::assertNull($this->plaintextStorage->loadTextFromFile($file, 'en'));
  }

  /**
   * @covers ::saveTextToFile
   */
  public function testSaveTextToFile(): void {
    // Create a file for testing.
    $file = File::create([
      'uri' => $this->createUri('foo.txt', 'veniam consequat duis'),
      'name' => 'foo',
    ]);
    $file->set('fid', 399);

    $file_path = $this->plaintextStorage->saveTextToFile($file, 'veniam consequat duis', 'en');
    self::assertStringEndsWith('private/entity-to-text/ocr/399-foo.txt.en.ocr.txt', $file_path);
    self::assertFileExists($file_path);
    self::assertEquals('veniam consequat duis', file_get_contents($file_path));
  }

  /**
   * @covers ::saveTextToFile
   */
  public function testSaveTextToFileWhenOcrFileAlreadyExists(): void {
    // Create an OCR file for testing.
    $file_ocr_uri = $this->createUri('400-foo.txt.en.ocr.txt', 'Ipsum excepteur id cupidatat commodo', 'private');
    $this->fileSystem->move($file_ocr_uri, 'private://entity-to-text/ocr/400-foo.txt.en.ocr.txt', FileSystemInterface::EXISTS_REPLACE);

    // Create a file for testing.
    $file = File::create([
      'uri' => $this->createUri('foo.txt', 'veniam consequat duis'),
      'name' => 'foo',
    ]);
    $file->set('fid', 400);

    // When the file already exists, it will be overriden.
    $file_path = $this->plaintextStorage->saveTextToFile($file, 'veniam consequat duis', 'en');
    self::assertStringEndsWith('private/entity-to-text/ocr/400-foo.txt.en.ocr.txt', $file_path);
    self::assertFileExists($file_path);
    self::assertEquals('veniam consequat duis', file_get_contents($file_path));
  }

}
