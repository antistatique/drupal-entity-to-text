<?php

namespace Drupal\entity_to_text_tika\Hook;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * RuntimeRequirements for entity to text tika.
 */
final class EntityToTextTikaRequirementsHook {
  use StringTranslationTrait;

  public function __construct(protected readonly StreamWrapperManagerInterface $streamWrapperManager, protected readonly FileSystemInterface $fileSystem) {
  }

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];

    $requirements['entity_to_text_tika_private'] = [
      'title' => $this->t('Entity to Text (Tika): Local File Storage (OCR cache)'),
      'description' => $this->t('Entity to Text Tika expose a Local File Storage optimisation in order to store OCR of document in the private:// schema. The current private schema configuration can leverage it.'),
      'value' => $this->t('Private file system is set and writtable.'),
    ];

    // Check if the private file stream wrapper is ready to use.
    if (!$this->streamWrapperManager->isValidScheme('private')) {
      $requirements['entity_to_text_tika_private']['value'] = 'Private file system is not set.';
      $requirements['entity_to_text_tika_private']['description'] = $this->t('Entity to Text Tika expose a Local File Storage optimisation in order to store OCR of document in the private:// schema. The current private schema configuration cannot leverage it.');
      $requirements['entity_to_text_tika_private']['severity'] = REQUIREMENT_INFO;
    }

    $private_path = $this->fileSystem->realpath('private://');
    // Check if the private file stream wrapper is ready to use.
    if (!is_dir($private_path) || !is_writable($private_path)) {
      $requirements['entity_to_text_tika_private']['value'] = $this->t('Entity to Text Tika expose a Local File Storage optimisation in order to store OCR of document in the private:// schema. The current private schema configuration cannot leverage it. The resolved private directory %directory% seems not writable.', ['%directory%' => $private_path]);
      $requirements['entity_to_text_tika_private']['severity'] = REQUIREMENT_ERROR;
    }

    return $requirements;
  }

}
