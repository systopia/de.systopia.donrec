<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

# require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Snapshot Test Suite
 */
class CRM_Donrec_TemplateTest extends CRM_Donrec_BaseTestCase {
  private $tablesToTruncate = array();

  function setUp(): void {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
    donrec_civicrm_install();
    donrec_civicrm_enable();
  }

  function tearDown(): void {
    parent::tearDown();
  }


  /**
   * Test template pdf generator
   *
   * @author niko bochan
   */
  public function testTemplateCreate() {
    $templates = CRM_Donrec_Logic_Template::findAllTemplates();
    $this->assertNotNULL($templates, "No template found!");

    $values = array('contributor' => 'TEST CONTRIBUTOR',
                    'total' => 100,
                    'total_text' => 'ein hundert Euro');
    $params = array();

    foreach ($templates as $key => $value) {
      $t = CRM_Donrec_Logic_Template::create($key);
      $this->assertNotNULL($t);
      $result = $t->generatePDF($values, $params);
      // check result
      $failed = !$result && !empty($params['is_error']);
      $error_msg = empty($params['is_error']) ? '' : $params['is_error'];
      $this->assertEquals(FALSE, $failed, sprintf('PDF creation failed: %s', $error_msg));
      $this->assertEquals(TRUE, $result);
      $this->assertEquals(TRUE, file_exists($result));
      // delete pdf
      $this->assertEquals(TRUE, unlink($result));
    }
  }

  /**
   * Test template pdf generator with invalid variables
   *
   * @author niko bochan
   */
  public function testTemplateCreateWithInvalid() {
    $templates = CRM_Donrec_Logic_Template::findAllTemplates();
    $this->assertNotNULL($templates, "No template found!");

    $values = array('contributor' => 'TEST CONTRIBUTOR',
                    'total' => 100,
                    'total_text' => 'ein hundert Euro');
    $params = array();

    foreach ($templates as $key => $value) {
      $t = CRM_Donrec_Logic_Template::create($key);
      $this->assertNotNULL($t);
      foreach ($values as $k => $v) {
        $values_copy = $values;
        unset($values_copy[$k]);
        $result = $t->generatePDF($values_copy, $params);
        // check result
        $failed = !$result && !empty($params['is_error']);
        $error_msg = empty($params['is_error']) ? '' : $params['is_error'];
        $this->assertEquals(FALSE, $failed, sprintf('PDF creation failed: %s', $error_msg));
        $this->assertEquals(TRUE, $result);
        $this->assertEquals(TRUE, file_exists($result));
        // delete pdf
        $this->assertEquals(TRUE, unlink($result));
      }
    }
  }
}