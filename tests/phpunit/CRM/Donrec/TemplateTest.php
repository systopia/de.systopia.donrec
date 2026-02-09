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
      'contributor' => ['display_name' => 'TEST CONTRIBUTOR'],
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
