<?php

namespace Drupal\entity_to_text_tika\Storage;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\Entity\File;

/**
 * Provide Capabilities to store a Text content into local plain-text file.
 */
class LocalFileStorage implements StorageInterface {

  public const DESTINATION = 'private://entity-to-text/ocr';

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Construct a new LocalFileStorage object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   The stream wrapper manager.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    LoggerChannelFactoryInterface $logger_factory,
    protected StreamWrapperManagerInterface $streamWrapperManager,
  ) {
    $this->logger = $logger_factory->get('entity_to_text_tika');
  }

  /**
   * {@inheritdoc}
   */
  public function load(File $file, string $langcode = 'eng'): ?string {
    $fullpath = $this->getFullPath($file, $langcode);

    if (!is_file($fullpath)) {
      return NULL;
    }

    return file_get_contents($fullpath);
  }

  /**
   * {@inheritdoc}
   */
  public function save(File $file, string $content, string $langcode = 'eng'): string {
    $fullpath = $this->getFullPath($file, $langcode);
    file_put_contents($fullpath, $content);
    return $fullpath;
  }

  /**
   * Get a normalized fullpath for a given file and langcode.
   *
   * @param \Drupal\file\Entity\File $file
   *   The document.
   * @param string $langcode
   *   The langcode.
   *
   * @return string
   *   The given file unique fullpath.
   */
  private function getFullPath(File $file, string $langcode = 'eng'): string {
    $uri = self::DESTINATION;
    $filename = $file->id() . '-' . $file->getFilename() . '.' . $langcode . '.ocr.txt';

    $scheme = StreamWrapperManager::getScheme($uri);
    if (!$this->streamWrapperManager->isValidScheme($scheme)) {
      throw new \RuntimeException('The destination path is not a valid stream wrapper.');
    }

    $path = $this->fileSystem->realpath($uri);
    if (!$path) {
      throw new \RuntimeException(sprintf('The resolved realpath from uri "%s" is not a valid directory.', $uri));
    }

    return $path . '/' . $filename;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareStorage(): void {
    $dest = self::DESTINATION;
    $this->fileSystem->prepareDirectory($dest, FileSystemInterface::CREATE_DIRECTORY);
  }

}
