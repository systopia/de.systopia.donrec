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
class CRM_Donrec_EngineTest extends CRM_Donrec_BaseTestCase {
  private $tablesToTruncate = array('civicrm_donrec_snapshot');

  function setUp() {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
  }

  function tearDown() {
    parent::tearDown();
  }


  /**
   * Test setup of the engine with a snapshot
   *
   * @author niko bochan
   */
  public function testEngineSetupWithValidSnapshot() {
    // create a new snapshot
    $contributions = $this->generateContributions(3);
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, 1);

    // engine setup parameters
    $sid = $snapshot->getId();
    $parameters = array();
    $parameters['test'] = 1;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = array('Dummy');

    // let's try to start it
    $engine = new CRM_Donrec_Logic_Engine();
    $engine_error = $engine->init($sid, $parameters);
    $user_id = CRM_Core_Session::singleton()->get('userID');
    error_log("id=" . $user_id);
    $this->assertEquals(FALSE, $engine_error);
  }
}