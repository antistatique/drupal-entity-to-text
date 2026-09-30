<?php

namespace Drupal\entity_to_text_tika\Extractor;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\entity_to_text_tika\Event\EntityToTextTikaEvents;
use Drupal\entity_to_text_tika\Event\PreProcessFileEvent;
use Drupal\file\Entity\File;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Vaites\ApacheTika\Client;

/**
 * Provide Capabilities to transform a File content to plain-text via Tika.
 */
class FileToText {

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Apache Tika client.
   *
   * @var \Vaites\ApacheTika\Client|null
   */
  protected $client;

  /**
   * Construct a new FileToText object.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The site settings.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    protected Settings $settings,
    protected FileSystemInterface $fileSystem,
    LoggerChannelFactoryInterface $logger_factory,
    private EventDispatcherInterface $eventDispatcher,
  ) {
    $this->logger = $logger_factory->get('entity_to_text');
    $this->client = NULL;
  }

  /**
   * Transform a File into plain text value.
   *
   * @param \Drupal\file\Entity\File $file
   *   The document.
   * @param string $langcode
   *   The OCR langcode to be used.
   *
   * @return string
   *   The transformed file into a plain text value by Apache Tika.
   */
  public function fromFileToText(File $file, string $langcode = 'eng'): string {
    $content = '';
    $settings_tika_connection = $this->settings->get('entity_to_text_tika.connection');

    // Don't attempts to query Tika when not configured.
    if (!isset($settings_tika_connection['host'], $settings_tika_connection['port'])) {
      return '';
    }

    /** @var \Vaites\ApacheTika\Clients\WebClient $web_client */
    $web_client = $this->getClient($settings_tika_connection['host'], $settings_tika_connection['port']);
    $web_client->setOCRLanguage($langcode);

    $event = new PreProcessFileEvent($web_client, $file);
    /** @var \Drupal\entity_to_text_tika\Event\PreProcessFileEvent $event */
    $event = $this->eventDispatcher->dispatch($event, EntityToTextTikaEvents::PRE_PROCESS_FILE);

    // Use the Event altered Client and File.
    $web_client = $event->getClient();
    $file = $event->getFile();

    $absolute_path = $this->fileSystem->realpath($file->getFileUri());

    try {
      $content = (string) $web_client->getText($absolute_path);
    }
    catch (\Exception $e) {
      $this->logger->notice("Document '@fid' on '@path' can't be processed by Tika. Got: @message.", [
        '@fid' => $file->id(),
        '@path' => $absolute_path,
        '@message' => $e->getMessage(),
      ]);
    }

    return $content;
  }

  /**
   * Get a class instance throwing an exception if check fails.
   *
   * @param string|null $param1
   *   Path or host.
   * @param int|string|null $param2
   *   Java binary path or port for web client.
   * @param array $options
   *   Options for cURL request.
   * @param bool $check
   *   Check JAR file or server connection.
   *
   * @return \Vaites\ApacheTika\Clients\CLIClient|\Vaites\ApacheTika\Clients\WebClient
   *   The Apache Tika Client.
   *
   * @throws \Exception
   */
  public function getClient(?string $param1 = NULL, int|string|null $param2 = NULL, array $options = [], bool $check = TRUE): Client {
    if (!$this->client) {
      $this->client = Client::make($param1, $param2, $options, $check);
      $this->client->setTimeout(60);
    }

    return $this->client;
  }

  /**
   * Set the Apache Tika client to be used.
   *
   * @param \Vaites\ApacheTika\Client $client
   *   The Apache client to be injected and used for later calls to Tika.
   */
  public function setClient(Client $client): void {
    $this->client = $client;
  }

}
