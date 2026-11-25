<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

/**
 * Snapshot Test Suite
 *
 * @covers \CRM_Donrec_Logic_Template
 * @group headless
 */
class CRM_Donrec_TemplateTest extends CRM_Donrec_BaseTestCase {

  private array $tablesToTruncate = [];

  protected function setUp(): void {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
    donrec_civicrm_install();
    donrec_civicrm_enable();
  }

  /**
   * Test template pdf generator
   *
   * @author niko bochan
   */
  public function testGeneratePDF(): void {
    $values = [
      'contributor' => [
        'id' => 1,
        'display_name' => 'TEST CONTRIBUTOR',
        'street_address' => 'Spenderstr. 1',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'supplemental_address_1' => '',
        'supplemental_address_2' => '',
        'country' => 'Deutschland',
      ],
      'organisation' => [
        'display_name' => 'Test Org',
        'organization_name' => 'Test Organisation e.V.',
        'street_address' => 'Teststr. 1',
        'supplemental_address_1' => '',
        'supplemental_address_2' => '',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
      ],
      'addressee' => [
        'display_name' => 'Test Empfänger',
        'street_address' => 'Teststr. 2',
        'supplemental_address_1' => '',
        'supplemental_address_2' => '',
        'postal_code' => '12345',
        'city' => 'Musterstadt',
        'country' => 'Deutschland',
      ],
      'total' => 100,
      'total_text' => 'ein hundert Euro',
    ];
    $params = [];

    $profile = CRM_Donrec_Logic_Profile::getProfile(0);
    $template = CRM_Donrec_Logic_Template::getTemplate($profile);
    $filename = $template->generatePDF($values, $params, $profile);
    static::assertIsString($filename);
    static::assertFileExists($filename);
    unlink($filename);
  }

}
