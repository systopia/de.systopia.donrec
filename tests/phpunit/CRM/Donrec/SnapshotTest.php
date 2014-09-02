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
 * Snapshot Test Suite
 */
class CRM_Donrec_SnapshotTest extends CRM_Donrec_BaseTestCase {
  function setUp() {
    // If your test manipulates any SQL tables, then you should truncate
    // them to ensure a consisting starting point for all tests
    // $this->quickCleanup(array('example_table_name'));
    parent::setUp();
  }

  function tearDown() {
    parent::tearDown();
  }


 /**
  * Test creation of a new snapshot
  *
  * @author niko bochan
  */
  public function testSnapshotCreation() {
    // prerequisites
    $contributions = ... // todo
    $originator_id = ... // todo

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if the snapshot table has been set correctly
  }

 /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotQueryMissing() {
    // prerequisites
    $contributions = ... // todo: (only one needed)
    $originator_id = ... // todo

    // generate no snapshot

    // todo: check if the contribution is part of a snapshot
    // result: there should be no match
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotQueryNoMatch() {
    // prerequisites
    $contributions       = ... // todo: (only two needed)
    $originator_id       = ... // todo:
    $target_contribution = ... // todo: (test against this one)

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if the contribution is part of a snapshot
    // result: there should be no match
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotQueryMatch() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if the contribution is part of a snapshot
    // result: there should be a match
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotQueryExpired() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if the contribution is part of an invalid/expired snapshot
    // result: there should be a match
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotDeletion() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate a new snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if a contribution is part of the snapshot
    // result: there should be a match

    // delete it
    $snapshot->delete();

    // todo: check if a contribution is part of the snapshot
    // result: there should be no match

    // todo: check via sql whether the snapshot has been removed completely
    // result: there should be no data
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotMaintenanceMissing() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate no snapshot

    // call maintenance method
    CRM_Donrec_Logic_Snapshot::cleanup();

    //todo: check for errors
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotMaintenanceNoExpired() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate a fresh snapshot (not expired)
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // call maintenance method
    CRM_Donrec_Logic_Snapshot::cleanup();

    //todo: check for errors / whether anything has been removed
    //result: there should be no changes
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotMaintenanceMixed() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate a new snapshot (not expired)
    $snapshot_fresh = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // generate an expired snapshot
    // todo: change method to easily create expired snapshots
    $snapshot_expired = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // call maintenance method
    CRM_Donrec_Logic_Snapshot::cleanup();

    //todo: check for errors / what has been removed
    //result: the expired one should be gone. the fresh snapshot should still be there.
  }

  /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotNoConflict() {
    // prerequisites
    $contributions       = ... // todo:
    $contributions_two   = ... // todo:
    $originator_id       = ... // todo:

    // generate a snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // generate a second snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions_two, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if there are intersections between the two snapshots
    // result: there should be none
    $result = CRM_Donrec_Logic_Snapshot::hasIntersections();
  }

   /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotConflict() {
    // prerequisites
    $contributions       = ... // todo:
    $originator_id       = ... // todo:

    // generate a snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // generate a second snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if there are intersections between the two snapshots
    // result: there should be one
    $result = CRM_Donrec_Logic_Snapshot::hasIntersections();
  }

   /**
  * 
  *
  * @author niko bochan
  */
  public function testSnapshotNoConflict() {
    // prerequisites
    $contributions       = ... // todo:
    $contributions_two   = ... // todo:
    $originator_id       = ... // todo:

    // generate a snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // generate a second, expired snapshot
    $snapshot = CRM_Donrec_Logic_Snapshot::create($contributions_two, $originator_id);
    $this->assertNotNull($snapshot, "CRM_Donrec_Logic_Snapshot::create() returned NULL");

    // todo: check if there are intersections between the two snapshots
    // result: there should be one
    $result = CRM_Donrec_Logic_Snapshot::hasIntersections();
  }
}