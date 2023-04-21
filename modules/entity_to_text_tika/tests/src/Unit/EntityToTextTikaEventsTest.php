<?php

namespace Drupal\Tests\entity_to_text_tika\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\entity_to_text_tika\Event\EntityToTextTikaEvents;

/**
 * @coversDefaultClass \Drupal\entity_to_text_tika\Event\EntityToTextTikaEvents
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 */
class EntityToTextTikaEventsTest extends UnitTestCase {

  /**
   * @covers \Drupal\entity_to_text_tika\Event\EntityToTextTikaEvents
   *
   * @dataProvider eventNames
   */
  public function testEventNames($event_name, $expected) {
    $this->assertEquals($expected, $event_name);
  }

  /**
   * List of supported event with expected names.
   *
   * @return array
   *   The list of CONST names & string expected value.
   */
  public function eventNames() {
    return [
      [
        EntityToTextTikaEvents::PRE_PROCESS_FILE,
        'entity_to_text_tika.preprocess_file',
      ],
    ];
  }

}
