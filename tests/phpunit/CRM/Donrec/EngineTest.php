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
 * Engine Test Suite
 *
 * @covers \CRM_Donrec_Logic_Engine
 * @group headless
 */
class CRM_Donrec_EngineTest extends CRM_Donrec_BaseTestCase {
  private array $tablesToTruncate = ['donrec_snapshot'];

  protected function setUp(): void {
    parent::setUp();
    $this->quickCleanup($this->tablesToTruncate);
  }

  /**
   * Test setup of the engine with a snapshot
   *
   * @author niko bochan
   */
  public function testEngineSetupWithValidSnapshot() {
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', 1);

    // create a new snapshot
    $contributions = $this->generateContributions(6);
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];

    // engine setup parameters
    $sid = $snapshot->getId();
    $parameters = [];
    $parameters['test'] = 1;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = 'Dummy';

    // let's try to start it
    $engine = new CRM_Donrec_Logic_Engine();
    $engine_error = $engine->init($sid, $parameters, TRUE);
    static::assertEquals(FALSE, $engine_error);

    $ctr = 0;
    foreach ($contributions as $id) {
      $stats = $engine->nextStep();
      $ctr++;
      $this->assertDBQuery(
        'TEST',
        sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $id)
      );
      $this->assertDBQuery(
        '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
        sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $id)
      );
      static::assertEquals($stats['count'], 6);
      static::assertEquals($stats['completed_test'], $ctr);
    }
  }

  public function testEngineSetupWithValidSnapshotSingle() {
    // create a new snapshot
    $contributions = $this->generateContributions(1);
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];

    // engine setup parameters
    $sid = $snapshot->getId();
    $parameters = [];
    $parameters['test'] = 1;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = 'Dummy';

    // let's try to start it
    $engine = new CRM_Donrec_Logic_Engine();
    $engine_error = $engine->init($sid, $parameters, TRUE);
    static::assertEquals(FALSE, $engine_error);

    $ctr = 0;
    foreach ($contributions as $id) {
      $stats = $engine->nextStep();
      $ctr++;
      $this->assertDBQuery(
        'TEST',
        sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $id)
      );
      $this->assertDBQuery(
        '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
        sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $id)
      );
      static::assertEquals($stats['count'], 1);
      static::assertEquals($stats['completed_test'], $ctr);
    }
  }

  public function testEngineWithOverlappingEngines() {
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', 1);

    // create a new snapshot
    $contributionsA = $this->generateContributions(5);
    $result = CRM_Donrec_Logic_Snapshot::create($contributionsA, 1, '2026-01-02', '2026-01-03', 1);
    $snapshotA = $result['snapshot'];

    // engine setup parameters
    $sid = $snapshotA->getId();
    $parameters = [];
    $parameters['test'] = 0;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = 'Dummy';

    $engineA = new CRM_Donrec_Logic_Engine();
    $engine_error = $engineA->init($sid, $parameters, TRUE);
    static::assertEquals(FALSE, $engine_error);

    $stats = $engineA->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[0])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[0])
    );
    static::assertEquals(5, $stats['count']);
    static::assertEquals(1, $stats['completed']);

    $stats = $engineA->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[1])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[1])
    );
    static::assertEquals($stats['count'], 5);
    static::assertEquals($stats['completed'], 2);

    unset($engineA);

    $contributionsB = $this->generateContributions(3);
    $result = CRM_Donrec_Logic_Snapshot::create($contributionsB, 1, '2026-01-02', '2026-01-03', 1);
    $snapshotB = $result['snapshot'];

    $sid = $snapshotB->getId();
    $parameters = [];
    $parameters['test'] = 0;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = 'Dummy';

    $engineB = new CRM_Donrec_Logic_Engine();
    $engine_error = $engineB->init($sid, $parameters, TRUE);
    static::assertEquals(FALSE, $engine_error);

    $stats = $engineB->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsB[0])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsB[0])
    );
    static::assertEquals($stats['count'], 3);
    static::assertEquals($stats['completed'], 1);

    $stats = $engineB->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsB[1])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsB[1])
    );
    static::assertEquals($stats['count'], 3);
    static::assertEquals($stats['completed'], 2);

    $sid = $snapshotA->getId();
    $parameters = [];
    $parameters['test'] = 0;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = 'Dummy';

    $engineA = new CRM_Donrec_Logic_Engine();
    $engine_error = $engineA->init($sid, $parameters, TRUE);
    static::assertEquals(FALSE, $engine_error);

    $stats = $engineA->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[2])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[2])
    );
    static::assertEquals($stats['count'], 5);
    static::assertEquals($stats['completed'], 3);

    $stats = $engineA->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[3])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[3])
    );
    static::assertEquals($stats['count'], 5);
    static::assertEquals($stats['completed'], 4);

    $stats = $engineB->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsB[2])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsB[2])
    );
    static::assertEquals($stats['count'], 3);
    static::assertEquals($stats['completed'], 3);

    $stats = $engineA->nextStep();
    $this->assertDBQuery(
      'DONE',
      sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[4])
    );
    $this->assertDBQuery(
      '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
      sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $contributionsA[4])
    );
    static::assertEquals($stats['count'], 5);
    static::assertEquals($stats['completed'], 5);
  }

  public function testEngineMultiExport() {
    CRM_Donrec_Logic_Settings::set('donrec_packet_size', 1);

    // create a new snapshot
    $contributions = $this->generateContributions(6);
    $result = CRM_Donrec_Logic_Snapshot::create($contributions, 1, '2026-01-02', '2026-01-03', 0);
    $snapshot = $result['snapshot'];

    // engine setup parameters
    $sid = $snapshot->getId();
    $parameters = [];
    $parameters['test'] = 1;
    $parameters['bulk'] = 0;
    $parameters['exporters'] = 'Dummy';

    // let's try to start it
    $engine = new CRM_Donrec_Logic_Engine();
    $engine_error = $engine->init($sid, $parameters, TRUE);
    static::assertEquals(FALSE, $engine_error);

    $ctr = 0;
    foreach ($contributions as $id) {
      $stats = $engine->nextStep();
      $ctr++;
      $this->assertDBQuery(
        'TEST',
        sprintf('SELECT `status` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $id)
      );
      $this->assertDBQuery(
        '{"Dummy":{"test":"Dummy was here!"},"is_bulk":false}',
        sprintf('SELECT `process_data` FROM `donrec_snapshot` WHERE `contribution_id` = %d;', $id)
      );
      static::assertEquals($stats['count'], 6);
      static::assertEquals($stats['completed_test'], $ctr);
    }
  }

}
