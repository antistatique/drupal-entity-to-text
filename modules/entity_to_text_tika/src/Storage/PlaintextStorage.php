<?php

namespace Drupal\entity_to_text_tika\Storage;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\Entity\File;

/**
 * Provide Capabilities to store a Text content to plain-text file.
 */
class PlaintextStorage {

  public const DESTINATION = 'private://entity-to-text/ocr';

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Construct a new PlaintextStorage object.
   */
  public function __construct(FileSystemInterface $file_system, LoggerChannelFactoryInterface $logger_factory, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('entity_to_text_tika');
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * Store a plain text value into a file.
   *
   * @param \Drupal\file\Entity\File $file
   *   The document.
   * @param string $langcode
   *   The OCR langcode to be used.
   *
   * @return string|null
   *   The transformed file into a plain text value by Apache Tika.
   */
  public function loadTextFromFile(File $file, string $langcode = 'eng'): ?string {
    $fullpath = $this->getFullPath($file, $langcode);

    if (!is_file($fullpath)) {
      return NULL;
    }

    return file_get_contents($fullpath);
  }

  /**
   * Store a plain text value into a file.
   *
   * @param \Drupal\file\Entity\File $file
   *   The document to be saved.
   * @param string $content
   *   The plain-text document to be stored.
   * @param string $langcode
   *   The langcode.
   *
   * @return string
   *   The saved fullpath file.
   */
  public function saveTextToFile(File $file, string $content, string $langcode = 'eng'): string {
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
    $this->prepareDestination();

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
   * Ensure the destination directory is ready to use.
   */
  private function prepareDestination(): void {
    $dest = self::DESTINATION;
    $this->fileSystem->prepareDirectory($dest, FileSystemInterface::CREATE_DIRECTORY);
  }

}
