<?php

namespace Drupal\entity_to_text_tika\Storage;

use Drupal\file\Entity\File;

/**
 * OCR Storage interface.
 *
 * All class that store and retrieve OCR text values must implement
 * the interface.
 */
interface StorageInterface {

  /**
   * Load an OCR text value from a storage interface (file, database ...).
   *
   * @param \Drupal\file\Entity\File $file
   *   The document to be saved.
   * @param string $langcode
   *   The translation the ocr file must be retrieved.
   *
   * @return string|null
   *   The OCR plain-text value for the given file.
   */
  public function load(File $file, string $langcode = 'eng'): ?string;

  /**
   * Store an OCR text value into a storage interface (file, database ...).
   *
   * @param \Drupal\file\Entity\File $file
   *   The document to be saved.
   * @param string $content
   *   The plain-text document to be stored.
   * @param string $langcode
   *   The translation the ocr file must be stored.
   *
   * @return string
   *   The saved full path file.
   */
  public function save(File $file, string $content, string $langcode = 'eng'): string;

  /**
   * Ensure the storage is ready to store OCR text values.
   */
  public function prepareStorage(): void;

}
