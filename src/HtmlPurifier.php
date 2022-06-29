<?php

namespace Drupal\entity_to_text;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\File\FileSystemInterface;

/**
 * Create the default HTMLPurifier configuration to transform HTML to text.
 */
class HtmlPurifier {

  /**
   * The HTMLPurifier cache folder name.
   *
   * The folder will be located in the root of Drupal public path.
   *
   * @var string
   */
  protected const HTMLPURIFIER_CACHE_NAME = 'HtmlPurifier';

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Construct a new HtmlPurifier object.
   */
  public function __construct(FileSystemInterface $file_system) {
    $this->fileSystem = $file_system;
  }

  /**
   * Initialize a new instance of HTML Purifier with default configuration.
   *
   * @return \HTMLPurifier
   *   The HTMLPurifier object.
   */
  public function init(?\HTMLPurifier_Config $config = NULL): \HTMLPurifier {
    if (!$config instanceof \HTMLPurifier_Config) {
      $config = $this->getHtmlPurifierConfig();
    }

    return new \HTMLPurifier($config);
  }

  /**
   * Create the default HTMLPurifier configuration to transform HTML to text.
   *
   * @return \HTMLPurifier_Config
   *   The HTMLPurifier configuration object.
   */
  public function getHtmlPurifierConfig(): \HTMLPurifier_Config {
    $public_path = (string) $this->fileSystem->realpath('public://');
    $cache_path = $public_path . \DIRECTORY_SEPARATOR . self::HTMLPURIFIER_CACHE_NAME;
    $this->fileSystem->prepareDirectory($cache_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    // Write a non-public .htaccess on the cache directory.
    FileSecurity::writeHtaccess($cache_path);

    $config = \HTMLPurifier_Config::createDefault();
    $config->set('Cache.SerializerPath', $cache_path);

    // Remove empty elements.
    $config->set('AutoFormat.RemoveEmpty', TRUE);
    $config->set('HTML.AllowedElements', []);
    $config->set('CSS.AllowedProperties', []);

    return $config;
  }

}
