<?php

namespace Drupal\entity_to_text_tika\Event;

use Vaites\ApacheTika\Client;
use Drupal\file\Entity\File;
use Drupal\Component\EventDispatcher\Event;

/**
 * Event fired just before processing a file through Tika.
 *
 * Allow you to alter the client configurations or the file before OCR.
 */
class PreProcessFileEvent extends Event {

  /**
   * The Apache Tika client.
   *
   * @var \Vaites\ApacheTika\Client
   */
  protected $client;

  /**
   * The Drupal file to be processed by Tika OCR.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;

  /**
   * Constructs a PreProcessFileEvent object.
   *
   * @param \Vaites\ApacheTika\Client $client
   *   The Apache Tika client.
   * @param \Drupal\file\Entity\File $file
   *   The Drupal file to be processed by Tika OCR.
   */
  public function __construct(Client $client, File $file) {
    $this->client = $client;
    $this->file = $file;
  }

  /**
   * Get the Apache Tika client.
   *
   * @return \Vaites\ApacheTika\Client
   *   The Apache Tika client.
   */
  public function getClient(): Client {
    return $this->client;
  }

  /**
   * Get the Drupal file.
   *
   * @return \Drupal\file\Entity\File
   *   The Drupal file object.
   */
  public function getFile(): File {
    return $this->file;
  }

}
