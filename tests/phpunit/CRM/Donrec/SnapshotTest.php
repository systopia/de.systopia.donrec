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
 * @covers \CRM_Donrec_Logic_Snapshot
 * @group headless
 */
class CRM_Donrec_SnapshotTest extends CRM_Donrec_BaseTestCase {
  private array $tablesToTruncate = ['donrec_snapshot'];

  protected function setUp(): void {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
  }

  /**
   * Test creation of a new snapshot
   *
   * @author niko bochan
   */
  public function testSnapshotCreation() {
    // prerequisites
    $contributions = $this->generateContributions(3);
    static::assertEquals(3, count($contributions));

    // generate a new snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];
    static::assertNotNull($snapshot, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    $this->assertDBQuery(3, 'SELECT count(*) FROM `donrec_snapshot`');
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotQueryMissing() {
    // prerequisites
    $contributions = $this->generateContributions(1);
    static::assertEquals(1, count($contributions));

    // generate no snapshot

    // check if the contribution is part of a snapshot
    $result = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($contributions[0]);
    static::assertEquals(FALSE, $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotQueryNoMatch() {
    // prerequisites
    $contributions = $this->generateContributions(3);
    static::assertEquals(3, count($contributions));
    $target_contribution = $this->generateContributions(1);

    // generate a new snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];
    static::assertNotNull($snapshot, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // check if the contribution is part of the snapshot
    $result = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($target_contribution[0]);
    static::assertEquals(FALSE, $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotQueryMatch() {
    // prerequisites
    $contributions = $this->generateContributions(1);
    static::assertEquals(1, count($contributions));

    // generate a new snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];
    static::assertNotNull($snapshot, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // check if the contribution is part of a snapshot
    $result = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($contributions[0]);
    static::assertEquals(TRUE, $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotQueryExpired() {
    // prerequisites
    $contributions = $this->generateContributions(1);
    static::assertEquals(1, count($contributions));

    // generate an expired snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0, -10);
    $snapshot = $result['snapshot'];
    static::assertNotNull($snapshot, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // todo: check if the contribution is part of an invalid/expired snapshot
    $result = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($contributions[0]);
    static::assertEquals(FALSE, $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotDeletion() {
    // prerequisites
    $contributions = $this->generateContributions(1);
    static::assertEquals(1, count($contributions));

    // generate a new snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];
    static::assertNotNull($snapshot, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // check if a contribution is part of the snapshot
    $result = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($contributions[0]);
    static::assertEquals(TRUE, $result);

    // delete it
    $snapshot->delete();

    // check if a contribution is still part of a snapshot
    $result = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($contributions[0]);
    static::assertEquals(FALSE, $result);

    // check via sql whether the snapshot has been removed completely
    $this->assertDBQuery(0, 'SELECT count(*) FROM `donrec_snapshot`');
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotMaintenanceMissing() {
    // prerequisites
    $contributions = $this->generateContributions(1);
    static::assertEquals(1, count($contributions));

    // generate no snapshot

    // call maintenance method
    $result = CRM_Donrec_Logic_Snapshot::cleanup();
    static::assertEquals(FALSE, $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotMaintenanceNoExpired() {
    // prerequisites
    $contributions = $this->generateContributions(5);
    static::assertEquals(5, count($contributions));

    // generate a fresh snapshot (not expired)
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];
    static::assertNotNull($snapshot, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // call maintenance method
    $result = CRM_Donrec_Logic_Snapshot::cleanup();
    static::assertEquals(FALSE, $result);
    $this->assertDBQuery(5, 'SELECT count(*) FROM `donrec_snapshot`');
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotMaintenanceMixed() {
    // prerequisites
    $contributions_f = $this->generateContributions(5);
    static::assertEquals(5, count($contributions_f));
    $contributions_e = $this->generateContributions(5);
    static::assertEquals(5, count($contributions_e));

    // generate a new snapshot (not expired)
    $result = CRM_Donrec_Logic_Snapshot::create($contributions_f, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot_fresh = $result['snapshot'];
    static::assertNotNull($snapshot_fresh, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');
    $this->assertDBQuery(5, 'SELECT count(*) FROM `donrec_snapshot`');

    // generate an expired snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions_f, 1, '2026-01-02', '2026-01-03', 0, -20);
    $snapshot_expired = $result['snapshot'];
    static::assertNotNull($snapshot_expired, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');
    $this->assertDBQuery(10, 'SELECT count(*) FROM `donrec_snapshot`');

    // call maintenance method
    CRM_Donrec_Logic_Snapshot::cleanup();

    //result: the expired one should be gone. the fresh snapshot should still be there.
    $this->assertDBQuery(5, 'SELECT count(*) FROM `donrec_snapshot` WHERE `created_by` = 1');
    $this->assertDBQuery(0, 'SELECT count(*) FROM `donrec_snapshot` WHERE `created_by` = 2');
    $this->assertDBQuery(5, 'SELECT count(*) FROM `donrec_snapshot`');
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotNoConflict() {
    // prerequisites
    $contributions = $this->generateContributions(5);
    static::assertEquals(5, count($contributions));
    $contributions_two = $this->generateContributions(5);
    static::assertEquals(5, count($contributions_two));

    // generate a snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot1 = $result['snapshot'];
    static::assertNotNull($snapshot1, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // generate a second snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions_two, 2, '2026-01-02', '2026-01-03', 0);
    $snapshot2 = $result['snapshot'];
    static::assertNotNull($snapshot2, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // check if there are intersections between the two snapshots
    $result = CRM_Donrec_Logic_Snapshot::hasIntersections();
    static::assertEquals(FALSE, $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotConflict() {
    // prerequisites
    $contributions = $this->generateContributions(15);
    static::assertEquals(15, count($contributions));

    // generate a snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot1 = $result['snapshot'];
    static::assertNotNull($snapshot1, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // generate a second snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 2, '2026-01-02', '2026-01-03', 0);
    $snapshot2 = $result['snapshot'];
    static::assertNotNull($snapshot2, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');

    // check if there are intersections between the two snapshots
    $result = CRM_Donrec_Logic_Snapshot::hasIntersections();
    static::assertEquals([
      0,
      [1, 'Second Domain', date('Y-m-d H:i:s', time() + 86400)],
      [2, 'Default Organization', date('Y-m-d H:i:s', time() + 86400)],
    ], $result);
  }

  /**
   *
   *
   * @author niko bochan
   */
  public function testSnapshotConflictMixed() {
    // prerequisites
    $contributions = $this->generateContributions(5);
    static::assertEquals(5, count($contributions));

    // generate a snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot1 = $result['snapshot'];
    static::assertNotNull($snapshot1, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');
    $this->assertDBQuery(5, 'SELECT count(*) FROM `donrec_snapshot`');

    // generate a second, expired snapshot
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 2, '2026-01-02', '2026-01-03', 0, -10);
    $snapshot2 = $result['snapshot'];
    static::assertNotNull($snapshot2, 'CRM_Donrec_Logic_Snapshot::create() returned NULL');
    $this->assertDBQuery(10, 'SELECT count(*) FROM `donrec_snapshot`');

    // check if there are intersections between the two snapshots
    $result = CRM_Donrec_Logic_Snapshot::hasIntersections();
    static::assertEquals([
      0,
      [1, 'Second Domain', date('Y-m-d H:i:s', time() + 86400)],
      [2, 'Default Organization', date('Y-m-d H:i:s', time() - 10 * 86400)],
    ], $result);
  }

}
