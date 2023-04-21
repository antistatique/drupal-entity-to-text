<?php

namespace Drupal\entity_to_text_tika\Event;

/**
 * Defines events for the Entity To Text Tika module.
 */
final class EntityToTextTikaEvents {

  /**
   * Allow to alter the client configurations or the file before OCR.
   *
   * @Event
   *
   * @see \Drupal\entity_to_text_tika\Event\PreProcessFileEvent
   */
  const PRE_PROCESS_FILE = 'entity_to_text_tika.preprocess_file';

}
