<?php

namespace Drupal\entity_to_text_tika\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_to_text_tika\Extractor\FileToText;
use Drupal\entity_to_text_tika\Storage\StorageInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Drush Command to Warmup the Tika OCR Cache.
 */
class OcrWarmupCommand extends DrushCommands {

  /**
   * The number of object processed by pages.
   *
   * @var int
   */
  public const LIMIT_PAGER = 100;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The file storage service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;

  /**
   * The File to text service.
   *
   * @var \Drupal\entity_to_text_tika\Extractor\FileToText
   */
  protected $fileToText;

  /**
   * The Plain-text storage cache processor.
   *
   * @var \Drupal\entity_to_text_tika\Storage\StorageInterface
   */
  protected $localFileStorage;

  /**
   * Warmup OCR caches for Tika constructor.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, FileToText $file_to_text, StorageInterface $local_storage) {
    $this->connection = $connection;
    $this->fileStorage = $entity_type_manager->getStorage('file');
    $this->fileToText = $file_to_text;
    $this->localFileStorage = $local_storage;
  }

  /**
   * Warmup OCR Tika cache file for all Drupal Files.
   *
   * @param array $options
   *   (Optional) An array of options.
   *
   * @command entity_to_text:tika:warmup
   *
   * @option fid
   *   A File Identifier to generate OCR (warmup).
   * @option filemime
   *   File Mime to process.
   *   [defaults: 'application/pdf', 'image/jpeg', 'image/png', 'image/tiff',
   *   'application/msword',
   *   'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
   *   'application/vnd.ms-excel',
   *   'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
   *   ].
   * @option filesize-threshold
   *   The maximum file size in bytes a document can be to be processed.
   *   This is useful to avoid processing large files.
   *   [defaults: NULL].
   * @option stop-on-failure
   *   Stop processing on first failed (Ex. Tika's down).
   *   [defaults: FALSE].
   * @option force
   *   Force rewriting already existing OCR files.
   *   [defaults: FALSE].
   * @option no-progress
   *   Don't write any progress to the console.
   *   [defaults: FALSE].
   * @option dry-run
   *   Cycle through the files but don't process them.
   *   [defaults: FALSE].
   *
   * @aliases e2t:t:w
   *
   * @usage drush e2t:t:w
   *   Warmup all files that does not already have an associated .ocr file.
   * @usage drush e2t:t:w --force
   *   Warmup all files even if the files has already been processed before.
   * @usage drush e2t:t:w --fid=2
   *   Warmup the file with FID 2.
   * @usage drush e2t:t:w --filemime=application/pdf
   *   Warmup all PDF files.
   * @usage drush e2t:t:w --filesize-threshold=1000000
   *  Warmup all files that are lighter than 1Mb.
   */
  public function warmup(
    array $options = [
      'fid' => NULL,
      'filemime' => [
        'application/pdf', 'image/jpeg', 'image/png', 'image/tiff',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      ],
      'filesize-threshold' => NULL,
      'stop-on-failure' => FALSE,
      'force' => FALSE,
      'no-progress' => FALSE,
      'dry-run' => FALSE,
    ],
  ): void {
    $fid = $options['fid'];
    $filemime = (array) $options['filemime'];
    $filesize_threshold = $options['filesize-threshold'];
    $stop_on_failure = (bool) $options['stop-on-failure'];
    $force = (bool) $options['force'];
    $dry_run = (bool) $options['dry-run'];

    // Raise the database connection timeout to 70 seconds.
    // Tika default timeout is 60 seconds therefore having a lower value
    // for database timeout may lead to MySQL server has gone away.
    // @see \Drupal\entity_to_text_tika\Extractor\FileToText::getClient().
    $this->connection->query('SET wait_timeout = 70');

    $query = $this->fileStorage->getQuery();
    $query->accessCheck(FALSE);
    if ($fid) {
      $query->condition('fid', $fid);
    }
    else {
      $query->condition('filemime', $filemime, 'IN');
    }

    $base_query = clone $query;
    $total_objects = $query->count()->execute();

    if ($this->output->isVerbose()) {
      $this->io()->info(sprintf('Warmup %d Files.', $total_objects));
    }
    $progressbar_objects = new ProgressBar($this->output, $total_objects);
    $pages = (int) ceil($total_objects / self::LIMIT_PAGER);

    if (!$options['no-progress']) {
      $progressbar_objects->setFormat(' %page_current%/%page_max% | %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
      $progressbar_objects->setMessage((string) $pages, 'page_max');
      $progressbar_objects->start();
    }

    try {
      for ($page = 0; $page <= $pages - 1; ++$page) {
        $progressbar_objects->setMessage((string) $page, 'page_current');
        $base_query->range($page * self::LIMIT_PAGER, self::LIMIT_PAGER);
        $files = $base_query->execute();
        foreach ($files as $file) {
          $file = $this->fileStorage->load($file);

          $this->output()->writeln(sprintf('Processing file (%s) "%s".', $file->id(), $file->getFileUri()), OutputInterface::VERBOSITY_VERBOSE);

          if ($filesize_threshold && $file->getSize() > $filesize_threshold) {
            $this->output()->writeln(sprintf('File (%s) "%s" is too large to be processed (%d bytes).', $file->id(), $file->getFileUri(), $file->getSize()), OutputInterface::VERBOSITY_VERBOSE);
            $progressbar_objects->advance();
            continue;
          }

          if ($dry_run) {
            $progressbar_objects->advance();
            continue;
          }

          // Load the already OCR'ed file if possible.
          $body = $this->localFileStorage->load($file, 'eng+fra');

          if ($body === NULL || $force) {
            // When the OCR'ed file is not available, then run Tika over it
            // and store it for the next run.
            $body = $this->fileToText->fromFileToText($file, 'eng+fra');
            $this->localFileStorage->save($file, $body, 'eng+fra');
          }

          $progressbar_objects->advance();
        }
      }
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());
      if ($stop_on_failure) {
        return;
      }
    }

    $progressbar_objects->finish();
    if ($this->output->isVerbose()) {
      $this->io()->success('Successfully processed files.');
    }
  }

}
