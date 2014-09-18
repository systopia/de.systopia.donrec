<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Engine Test Suite
 */
class CRM_Donrec_ReceiptTest extends CRM_Donrec_BaseTestCase {
  private $tablesToTruncate = array();

  function setUp() {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
  }

  function tearDown() {
    //parent::tearDown();
  }

  public function testGetReceipt() {
    $r = CRM_Donrec_Logic_Receipt::getSingle(117, 1);
    $this->assertNotNull($r);
  }


}