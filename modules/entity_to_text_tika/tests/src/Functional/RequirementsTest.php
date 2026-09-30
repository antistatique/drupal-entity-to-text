<?php

namespace Drupal\Tests\entity_to_text_tika\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests module requirements.
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 * @group entity_to_text_functional
 * @group entity_to_text_tika_functional
 */
#[Group('entity_to_text')]
#[Group('entity_to_text_tika')]
#[Group('entity_to_text_functional')]
#[Group('entity_to_text_tika_functional')]
#[RunTestsInSeparateProcesses]
class RequirementsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_to_text_tika'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
    ]);
  }

  /**
   * Tests when private stream is configured the status acknowledge.
   */
  public function testStatusPageGood() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/reports/status');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Entity to Text (Tika): Local File Storage (OCR cache)');
    $this->assertSession()->pageTextContains('Private file system is set and writtable.');
  }

}
