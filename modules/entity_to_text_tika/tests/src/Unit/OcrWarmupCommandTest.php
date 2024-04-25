<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\entity_to_text_tika\Commands\OcrWarmupCommand;
use Drupal\entity_to_text_tika\Extractor\FileToText;
use Drupal\entity_to_text_tika\Storage\StorageInterface;
use Drupal\file\Entity\File;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @coversDefaultClass \Drupal\entity_to_text_tika\Commands\OcrWarmupCommand
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 *
 * @internal
 */
final class OcrWarmupCommandTest extends UnitTestCase {

  /**
   * A mocked file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

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
   * @var \Drupal\entity_to_text_tika\Commands\OcrWarmupCommand
   */
  protected $warmupCommand;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileStorage = $this->createMock(EntityStorageInterface::class);
    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects(self::once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($this->fileStorage);

    $this->localFileStorage = $this->createMock(StorageInterface::class);
    $this->fileToText = $this->createMock(FileToText::class);

    $this->warmupCommand = new OcrWarmupCommand(
      $entity_type_manager,
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

    $this->fileStorage->expects(self::once())
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

    $this->fileStorage->expects($this->exactly(2))
      ->method('load')
      ->withConsecutive(
        [200],
        [2039],
      )
      ->willReturnOnConsecutiveCalls($file200, $file2039);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('load')
      ->withConsecutive(
        [$file200, 'eng+fra'],
        [$file2039, 'eng+fra'],
      )
      ->willReturnOnConsecutiveCalls('lorem ipsum', NULL);

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

    $this->fileStorage->expects(self::once())
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

    $this->fileStorage->expects($this->exactly(2))
      ->method('load')
      ->withConsecutive(
        [200],
        [2039],
      )
      ->willReturnOnConsecutiveCalls($file200, $file2039);

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

    $this->fileStorage->expects(self::once())
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

    $this->fileStorage->expects($this->exactly(2))
      ->method('load')
      ->withConsecutive(
        [200],
        [2039],
      )
      ->willReturnOnConsecutiveCalls($file200, $file2039);

    $this->localFileStorage->expects($this->exactly(2))
      ->method('load')
      ->withConsecutive(
        [$file200, 'eng+fra'],
        [$file2039, 'eng+fra'],
      )
      ->willReturnOnConsecutiveCalls('lorem ipsum', NULL);

    $this->fileToText->expects($this->exactly(2))
      ->method('fromFileToText')
      ->withConsecutive(
        [$file200, 'eng+fra'],
        [$file2039, 'eng+fra'],
      )
      ->willReturn('doloreum', 'ipsum');

    $this->localFileStorage->expects($this->exactly(2))
      ->method('save')
      ->withConsecutive(
        [$file2039, 'doloreum', 'eng+fra'],
        [$file2039, 'ipsum', 'eng+fra'],
      );

    $this->warmupCommand->warmup([
      'fid' => NULL,
      'filemime' => [
        'application/pdf',
      ],
      'filesize-threshold' => NULL,
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

    $this->fileStorage->expects(self::once())
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

    $this->fileStorage->expects($this->once())
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
      'stop-on-failure' => FALSE,
      'force' => FALSE,
      'no-progress' => FALSE,
      'dry-run' => FALSE,
    ]);
  }

}
