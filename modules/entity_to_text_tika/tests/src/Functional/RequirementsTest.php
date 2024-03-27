<?php

namespace Drupal\Tests\entity_to_text_tika\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests module requirements.
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 * @group entity_to_text_tika_functional
 */
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

    $this->assertSession()->pageTextContains('Entity to Text (Tika): Private schema');
    $this->assertSession()->pageTextContains('Private file system is set and writtable.');
  }

}
