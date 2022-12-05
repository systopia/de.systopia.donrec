<?php

use CRM_Donrec_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\CiviEnvBuilder;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Donrec_PdfcryptTest extends CRM_Donrec_BaseTestCase {

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp():void {
    parent::setUp();
  }

  public function tearDown():void {
    parent::tearDown();
  }

  /*
   * tests that our settings are set
   */
  public function testSettings():void {
    $settings = ['donrec_civioffice_document_renderer_uri','donrec_civioffice_document_uri','donrec_pdfunite_path','donrec_pdfinfo_path','donrec_packet_size','donrec_enable_line_item','donrec_crypt_command','donrec_enable_crypt'];
    foreach ($settings as &$val) {
      $fields = \Civi\Api4\Setting::getFields()
        ->addWhere('name', '=', $val)
        ->execute();
      $this->assertCount(1, $fields);
    }
  }

  public function testSettingsPage(): void {
    $_SERVER['REQUEST_URI'] = 'civicrm/admin/setting/donrec?reset=1';
    $_GET['q'] = 'civicrm/admin/setting/donrec';
    $_GET['reset'] = 1;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['reset']);
    $this->assertStringContainsString('External Tool: command line for encryption', $contents);
  }

  public function testProfileSettingsPage(): void {
    $_SERVER['REQUEST_URI'] = 'civicrm/admin/setting/donrec/profile?op=edit&id=1';
    $_GET['q'] = 'civicrm/admin/setting/donrec/profile';
    $_GET['op'] = $_REQUEST['op'] = 'edit';
    $_GET['id'] = $_REQUEST['id'] = 1;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['op']); unset($_REQUEST['op']);
    unset($_GET['id']); unset($_REQUEST['id']);
    # unset($_GET['reset']);
    $this->assertStringContainsString('Enable "encryption"', $contents);
    $this->assertStringContainsString('<input id="enable_encryption" name="enable_encryption" type="checkbox" value="1" class="crm-form-checkbox" />', $contents);
  }

}
