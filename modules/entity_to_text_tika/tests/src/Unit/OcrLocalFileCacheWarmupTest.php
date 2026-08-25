<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\entity_to_text_tika\Commands\OcrLocalFileCacheWarmup;
use Drupal\entity_to_text_tika\Extractor\FileToText;
use Drupal\entity_to_text_tika\Storage\StorageInterface;
use Drupal\file\Entity\File;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @coversDefaultClass \Drupal\entity_to_text_tika\Commands\OcrLocalFileCacheWarmup
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 *
 * @internal
 */
final class OcrLocalFileCacheWarmupTest extends UnitTestCase {

  /**
   * A mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A mocked Tika File to text service.
   *
   * @var \Drupal\entity_to_text_tika\Extractor\FileToText
   */
  protected $fileToText;

  /**
   * A mocked Plain-text storage processor.
   *
   * @var \Drupal\entity_to_text_tika\Storage\LocalFileStorage
   */
  protected $localFileStorage;

  /**
   * The command to test.
   *
   * @var \Drupal\entity_to_text_tika\Commands\OcrLocalFileCacheWarmup
   */
  protected $warmupCommand;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $database = $this->createMock(Connection::class);
    $database->expects(self::once())
      ->method('query')
      ->with('SET wait_timeout = 70');

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->localFileStorage = $this->createMock(StorageInterface::class);
    $this->fileToText = $this->createMock(FileToText::class);

    $this->warmupCommand = new OcrLocalFileCacheWarmup(
      $database,
      $this->entityTypeManager,
      $this->fileToText,
      $this->localFileStorage,
    );

    $input = $this->createMock(InputInterface::class);
    $output = $this->createMock(OutputInterface::class);

    // Handle Drush 10 vs Drush 11 differences.
    $output->method('getFormatter')->willReturn($this->createMock(OutputFormatterInterface::class));

    $this->warmupCommand->setInput($input);
    $this->warmupCommand->setOutput($output);
  }

  /**
   * @covers ::warmup
   */
  public function testWarmup(): void {
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE);
    $query->expects($this->once())
      ->method('condition')
      ->with('filemime', [
        'application/pdf', 'image/jpeg', 'image/png', 'image/tiff',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ]);
    $query->expects($this->once())
      ->method('count')
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('execute')
      ->willReturnOnConsecutiveCalls(
        // The first call is the cound query.
        2,
        // The second call is the actual query with files IDs.
        [200, 2039],
      );
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100);

    $file_storage->expects(self::once())
      ->method('getQuery')
      ->willReturn($query);

    // Create a test file object.
    $file200 = $this->createMock(File::class);
    $file200->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/test.txt');
    $file200->expects(self::once())
      ->method('id')
      ->willReturn(200);

    // Create a test file object.
    $file2039 = $this->createMock(File::class);
    $file2039->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/foo.pdf');
    $file2039->expects(self::once())
      ->method('id')
      ->willReturn(2039);

    $file_storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [200, $file200],
        [2039, $file2039],
      ]);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [$file200, 'eng+fra', 'lorem ipsum'],
        [$file2039, 'eng+fra', NULL],
      ]);

    $this->fileToText->expects($this->once())
      ->method('fromFileToText')
      ->with($file2039, 'eng+fra')
      ->willReturn('doloreum');

    $this->localFileStorage->expects($this->once())
      ->method('save')
      ->with($file2039, 'doloreum', 'eng+fra');

    $this->warmupCommand->warmup();
  }

  /**
   * @covers ::warmup
   */
  public function testWarmupDryrun(): void {
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE);
    $query->expects($this->once())
      ->method('condition')
      ->with('filemime', ['application/pdf']);
    $query->expects($this->once())
      ->method('count')
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('execute')
      ->willReturnOnConsecutiveCalls(
      // The first call is the cound query.
        2,
        // The second call is the actual query with files IDs.
        [200, 2039],
      );
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100);

    $file_storage->expects(self::once())
      ->method('getQuery')
      ->willReturn($query);

    // Create a test file object.
    $file200 = $this->createMock(File::class);
    $file200->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/test.txt');
    $file200->expects(self::once())
      ->method('id')
      ->willReturn(200);

    // Create a test file object.
    $file2039 = $this->createMock(File::class);
    $file2039->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/foo.pdf');
    $file2039->expects(self::once())
      ->method('id')
      ->willReturn(2039);

    $file_storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [200, $file200],
        [2039, $file2039],
      ]);

    $this->localFileStorage->expects($this->never())
      ->method('load');

    $this->fileToText->expects($this->never())
      ->method('fromFileToText');

    $this->localFileStorage->expects($this->never())
      ->method('save');

    $this->warmupCommand->warmup([
      'fid' => NULL,
      'filemime' => [
        'application/pdf',
      ],
      'filesize-threshold' => NULL,
      'save-empty-ocr' => FALSE,
      'stop-on-failure' => FALSE,
      'force' => FALSE,
      'no-progress' => FALSE,
      'dry-run' => TRUE,
    ]);
  }

  /**
   * @covers ::warmup
   */
  public function testWarmupForce(): void {
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE);
    $query->expects($this->once())
      ->method('condition')
      ->with('filemime', ['application/pdf']);
    $query->expects($this->once())
      ->method('count')
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('execute')
      ->willReturnOnConsecutiveCalls(
        // The first call is the count query.
        2,
        // The second call is the actual query with files IDs.
        [200, 2039],
      );
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100);

    $file_storage->expects(self::once())
      ->method('getQuery')
      ->willReturn($query);

    // Create a test file object.
    $file200 = $this->createMock(File::class);
    $file200->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/test.txt');
    $file200->expects(self::any())
      ->method('id')
      ->willReturn(200);

    // Create a test file object.
    $file2039 = $this->createMock(File::class);
    $file2039->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/foo.pdf');
    $file2039->expects(self::any())
      ->method('id')
      ->willReturn(2039);

    $file_storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [200, $file200],
        [2039, $file2039],
      ]);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [$file200, 'eng+fra', 'lorem ipsum'],
        [$file2039, 'eng+fra', NULL],
      ]);

    $this->fileToText->expects($this->exactly(2))
      ->method('fromFileToText')
      ->willReturnMap([
        [$file200, 'eng+fra', 'doloreum'],
        [$file2039, 'eng+fra', 'ipsum'],
      ]);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('save')
      ->with(
        $this->callback(function ($arg) use ($file200, $file2039) {
          // First arg should be a File object (one of our two mocks)
          $isCorrectFile = ($arg === $file200 || $arg === $file2039);
          return $isCorrectFile;
        }),
        $this->logicalOr(
          $this->equalTo('doloreum'),
          $this->equalTo('ipsum')
        ),
        $this->equalTo('eng+fra')
      );

    $this->warmupCommand->warmup([
      'fid' => NULL,
      'filemime' => [
        'application/pdf',
      ],
      'filesize-threshold' => NULL,
      'save-empty-ocr' => FALSE,
      'stop-on-failure' => FALSE,
      'force' => TRUE,
      'no-progress' => FALSE,
      'dry-run' => FALSE,
    ]);
  }

  /**
   * @covers ::warmup
   */
  public function testWarmupFid(): void {
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE);
    $query->expects($this->once())
      ->method('condition')
      ->with('fid', 200);
    $query->expects($this->once())
      ->method('count')
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('execute')
      ->willReturnOnConsecutiveCalls(
        // The first call is the cound query.
        1,
        // The second call is the actual query with files IDs.
        [200],
      );
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100);

    $file_storage->expects(self::once())
      ->method('getQuery')
      ->willReturn($query);

    // Create a test file object.
    $file200 = $this->createMock(File::class);
    $file200->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/test.txt');
    $file200->expects(self::once())
      ->method('id')
      ->willReturn(200);

    $file_storage->expects($this->once())
      ->method('load')
      ->with(200)
      ->willReturn($file200);

    $this->localFileStorage->expects($this->once())
      ->method('load')
      ->with($file200, 'eng+fra')
      ->willReturn(NULL);

    $this->fileToText->expects($this->once())
      ->method('fromFileToText')
      ->with($file200, 'eng+fra')
      ->willReturn('Carpe diem');

    $this->localFileStorage->expects($this->once())
      ->method('save')
      ->with($file200, 'Carpe diem', 'eng+fra');

    $this->warmupCommand->warmup([
      'fid' => 200,
      'filemime' => [
        'application/pdf',
      ],
      'filesize-threshold' => NULL,
      'save-empty-ocr' => FALSE,
      'stop-on-failure' => FALSE,
      'force' => FALSE,
      'no-progress' => FALSE,
      'dry-run' => FALSE,
    ]);
  }

  /**
   * @covers ::warmup
   */
  public function testWarmupSaveEmptyOcr(): void {
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE);
    $query->expects($this->once())
      ->method('condition')
      ->with('filemime', [
        'application/pdf',
      ]);
    $query->expects($this->once())
      ->method('count')
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('execute')
      ->willReturnOnConsecutiveCalls(
      // The first call is the cound query.
        2,
        // The second call is the actual query with files IDs.
        [200, 2039],
      );
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100);

    $file_storage->expects(self::once())
      ->method('getQuery')
      ->willReturn($query);

    // Create a test file object.
    $file200 = $this->createMock(File::class);
    $file200->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/test.txt');
    $file200->expects(self::once())
      ->method('id')
      ->willReturn(200);

    // Create a test file object.
    $file2039 = $this->createMock(File::class);
    $file2039->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/foo.pdf');
    $file2039->expects(self::once())
      ->method('id')
      ->willReturn(2039);

    $file_storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [200, $file200],
        [2039, $file2039],
      ]);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [$file200, 'eng+fra', 'lorem ipsum'],
        [$file2039, 'eng+fra', NULL],
      ]);

    $this->fileToText->expects($this->once())
      ->method('fromFileToText')
      ->with($file2039, 'eng+fra')
      ->willReturn('');

    $this->localFileStorage->expects($this->once())
      ->method('save')
      ->with($file2039, '', 'eng+fra');

    $this->warmupCommand->warmup([
      'fid' => NULL,
      'filemime' => [
        'application/pdf',
      ],
      'filesize-threshold' => NULL,
      'save-empty-ocr' => TRUE,
      'stop-on-failure' => FALSE,
      'force' => FALSE,
      'no-progress' => FALSE,
      'dry-run' => FALSE,
    ]);
  }

  /**
   * @covers ::warmup
   */
  public function testWarmupNoSaveEmptyOcr(): void {
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $query = $this->createMock(QueryInterface::class);
    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE);
    $query->expects($this->once())
      ->method('condition')
      ->with('filemime', [
        'application/pdf',
      ]);
    $query->expects($this->once())
      ->method('count')
      ->willReturnSelf();
    $query->expects($this->exactly(2))
      ->method('execute')
      ->willReturnOnConsecutiveCalls(
      // The first call is the cound query.
        2,
        // The second call is the actual query with files IDs.
        [200, 2039],
      );
    $query->expects($this->once())
      ->method('range')
      ->with(0, 100);

    $file_storage->expects(self::once())
      ->method('getQuery')
      ->willReturn($query);

    // Create a test file object.
    $file200 = $this->createMock(File::class);
    $file200->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/test.txt');
    $file200->expects(self::once())
      ->method('id')
      ->willReturn(200);

    // Create a test file object.
    $file2039 = $this->createMock(File::class);
    $file2039->expects(self::once())
      ->method('getFileUri')
      ->willReturn('public://file/foo.pdf');
    $file2039->expects(self::once())
      ->method('id')
      ->willReturn(2039);

    $file_storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [200, $file200],
        [2039, $file2039],
      ]);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('load')
      ->willReturnMap([
        [$file200, 'eng+fra', 'lorem ipsum'],
        [$file2039, 'eng+fra', NULL],
      ]);

    $this->fileToText->expects($this->once())
      ->method('fromFileToText')
      ->with($file2039, 'eng+fra')
      ->willReturn('');

    $this->localFileStorage->expects($this->never())
      ->method('save');

    $this->warmupCommand->warmup([
      'fid' => NULL,
      'filemime' => [
        'application/pdf',
      ],
      'filesize-threshold' => NULL,
      'save-empty-ocr' => FALSE,
      'stop-on-failure' => FALSE,
      'force' => FALSE,
      'no-progress' => FALSE,
      'dry-run' => FALSE,
    ]);
  }

}
