<?php

namespace Drupal\Tests\entity_to_text_tika\Functional;

use Drupal\Tests\system\Functional\Module\ModuleTestBase;

/**
 * Tests install / uninstall of module.
 *
 * @group entity_to_text
 * @group entity_to_text_tika
 * @group entity_to_text_tika_functional
 */
class InstallUninstallTest extends ModuleTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * Ensure module can be installed.
   */
  public function testInstall(): void {
    // Makes sure the base module is installed.
    $this->container->get('module_installer')->install(['entity_to_text']);
    // Makes sure the sub-module is not already installed.
    $this->assertModules(['entity_to_text_tika'], FALSE);

    // Attempt to install the module.
    $edit = [];
    $edit['modules[entity_to_text][enable]'] = 'entity_to_text';
    $edit['modules[entity_to_text_tika][enable]'] = 'entity_to_text_tika';
    $this->drupalGet('admin/modules');
    $this->submitForm($edit, 'Install');

    $this->assertSession()->pageTextContains('Module Entity to Text - Tika has been enabled.');

    // Makes sure the module has been installed.
    $this->assertModules(['entity_to_text_tika'], TRUE);
  }

  /**
   * Ensure module can be uninstalled.
   */
  public function testUninstall(): void {
    // Makes sure the base module is installed.
    $this->container->get('module_installer')->install(['entity_to_text']);
    // Makes sure the sub-module is installed.
    $this->container->get('module_installer')->install(['entity_to_text_tika']);

    // Attempt to uninstall the factory_lollipop module.
    $edit['uninstall[entity_to_text_tika]'] = TRUE;
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm($edit, 'Uninstall');
    // Confirm uninstall.
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->responseContains('The selected modules have been uninstalled.');

    // Makes sure the module has been uninstalled.
    $this->assertModules(['entity_to_text_tika'], FALSE);
  }

}
