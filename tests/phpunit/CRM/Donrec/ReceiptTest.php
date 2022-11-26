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
 * Engine Test Suite
 */
class CRM_Donrec_ReceiptTest extends CRM_Donrec_BaseTestCase {
  private $tablesToTruncate = array('donrec_snapshot');

  function setUp(): void {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
  }

  function tearDown(): void {
    //parent::tearDown();
  }

  function testCreateSingle() {
    // prerequisites
    $contributions       = $this->generateContributions(3);
    $this->assertEquals(3, count($contributions));

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, 1);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // create a receipt
    $snapshot_line_id = 1;
    $params = array();
    $result = CRM_Donrec_Logic_Receipt::createSingleFromSnapshot($snapshot, $snapshot_line_id, $params);
    $this->assertEquals(TRUE, $result, "CRM_Donrec_Logic_Receipt::createSingleFromSnapshot returned FALSE");
  }

  function testCreateBulk() {
    // prerequisites
    $contributions       = $this->generateContributions(3);
    $this->assertEquals(3, count($contributions));

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, 1);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // create a receipt
    $snapshot_line_ids = array(1,2,3);
    $params = array();
    $result = CRM_Donrec_Logic_Receipt::createBulkFromSnapshot($snapshot, $snapshot_line_ids, $params);
    $this->assertEquals(TRUE, $result, "CRM_Donrec_Logic_Receipt::createSingleFromSnapshot returned FALSE");
  }

  function testCopy() {
    // prerequisites
    $contributions       = $this->generateContributions(3);
    $this->assertEquals(3, count($contributions));

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, 1);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // create a receipt
    $snapshot_line_id = 1;
    $params = array();
    $result = CRM_Donrec_Logic_Receipt::createSingleFromSnapshot($snapshot, $snapshot_line_id, $params);
    $this->assertEquals(TRUE, $result, "CRM_Donrec_Logic_Receipt::createSingleFromSnapshot returned FALSE");

    // clone it
    
  }

  function testGetDisplayProperties() {

  }

  function testGetAllProperties() {

  }


}