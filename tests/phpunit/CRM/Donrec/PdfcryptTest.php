<?php

declare(strict_types = 1);

/**
 * @group headless
 */
class CRM_Donrec_PdfcryptTest extends CRM_Donrec_BaseTestCase {

  /**
   *
   * tests that our settings are set
   *
   * @coversNothing
   */
  public function testSettings(): void {
    $settings = [
      'donrec_civioffice_document_renderer_uri',
      'donrec_civioffice_document_uri',
      'donrec_pdfunite_path',
      'donrec_pdfinfo_path',
      'donrec_packet_size',
      'donrec_enable_line_item',
      'donrec_encryption_command',
      'donrec_enable_crypt',
    ];
    foreach ($settings as $val) {
      $fields = \Civi\Api4\Setting::getFields(FALSE)
        ->addWhere('name', '=', $val)
        ->execute();
      static::assertCount(1, $fields);
    }
  }

  /**
   * test basic function of general setting for encryption tool
   *
   * @covers CRM_Admin_Form_Setting_DonrecSettings
   */
  public function testSettingsPage(): void {
    $this->markTestSkipped('Is not working, yet.');
    $_SERVER['REQUEST_URI'] = 'civicrm/admin/setting/donrec?reset=1';
    $_GET['q'] = 'civicrm/admin/setting/donrec';
    $_GET['reset'] = 1;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['reset']);
    static::assertIsString($contents);
    static::assertStringContainsString('External Tool: command line for encryption', $contents);
  }

  /**
   * test basic function of the enable encryption setting on profile page
   *
   * @covers CRM_Admin_Page_DonrecProfiles
   */
  public function testProfileSettingsPage(): void {
    $this->markTestSkipped('Is not working, yet.');
    $_SERVER['REQUEST_URI'] = 'civicrm/admin/setting/donrec/profile?op=edit&id=1';
    $_GET['q'] = 'civicrm/admin/setting/donrec/profile';
    $_GET['op'] = $_REQUEST['op'] = 'edit';
    $_GET['id'] = $_REQUEST['id'] = 1;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['op']);
    unset($_REQUEST['op']);
    unset($_GET['id']);
    unset($_REQUEST['id']);
    static::assertIsString($contents);
    static::assertStringContainsString('Enable "encryption"', $contents);
    static::assertStringContainsString(
      '<input id="enable_encryption" name="enable_encryption" type="checkbox" value="1" class="crm-form-checkbox" />',
      $contents
    );
  }

  /**
   * test if our new form element exists
   *
   * @covers CRM_Admin_Form_DonrecProfile
   */
  public function testElementExists(): void {
    $form = new CRM_Admin_Form_DonrecProfile();
    $form->controller = new CRM_Core_Controller();
    $form->buildForm();
    static::assertNotNull($form->elementExists('enable_encryption'));
  }

  // test if we can enable encryption in the profile settings

  /**
   * TODO: take the approach from testElementExists() instead
   *
   * @covers CRM_Admin_Form_DonrecProfile
   */
  public function testProfileSettingsEnableEncryption(): void {
    $this->markTestSkipped('Is not working, yet.');

    $profile = CRM_Donrec_Logic_Profile::getProfile(0);
    $profile->setName('test');
    $profile->save();
    $_SERVER['REQUEST_URI'] = 'civicrm/admin/setting/donrec/profile?op=edit&id=' . $profile->getId();
    $_GET['q'] = 'civicrm/admin/setting/donrec/profile';
    $_POST['enable_encryption'] = '1';
    $_GET['op'] = $_REQUEST['op'] = 'edit';
    $_GET['id'] = $_REQUEST['id'] = $profile->getId();

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    $profile = CRM_Donrec_Logic_Profile::getProfile($profile->getId());
    static::assertEquals(1, $profile->getDataAttribute('enable_encryption'));
  }

}
