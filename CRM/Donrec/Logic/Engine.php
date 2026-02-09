<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class represents the engine for donation receipt runs
 */
class CRM_Donrec_Logic_Engine {

  /**
   * stores the related snapshot object
   */
  protected ?CRM_Donrec_Logic_Snapshot $snapshot = NULL;

  /**
   * stores the parameters as given by the user
   *
   * known parameters:
   *  exporters  array(exporter_classes)
   *  bulk       1 or 0 - if 1, accumulative (='bulk') donation receipts should be issued
   *  test       1 or 0 - if 0, the contributions will not actually be marked as 'receipt_issued'
   */
  protected array $parameters = [];

  /**
   * will cache initialized instances of the exporters as defined in the parameters
   */
  protected ?array $_exporters = NULL;

  /**
   * Will try to initialize the engine with a snapshot
   * and the given parameters. If anything is wrong,
   * an error message will be returned.
   *
   * @param int $snapshot_id
   * @param array $params
   * @param bool $testMode
   *
   * @return string|FALSE string with an error message on fail, FALSE otherwise
   * @throws \CRM_Core_Exception
   */
  public function init($snapshot_id, $params = [], $testMode = FALSE) {
    $this->parameters = $params;
    $this->snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    if ($this->snapshot == NULL) {
      return sprintf(E::ts('Snapshot [%d] does not exist (any more)!'), $snapshot_id);
    }

    // now, check if it's ours:
    $user_id = CRM_Core_Session::singleton()->get('userID');
    $snapshot_creator_id = $this->snapshot->getCreator();
    if ($user_id != $snapshot_creator_id && (!$testMode)) {
      // load creator name
      $creator = civicrm_api3('Contact', 'getsingle', ['id' => $snapshot_creator_id]);
      return sprintf(
        E::ts("Snapshot [%d] belongs to user '%s'[%s]!"),
        $snapshot_id,
        $creator['display_name'],
        $snapshot_creator_id
      );
    }

    // now, if this is supposed to be test mode, there must not be a real status
    if ($this->isTestRun()) {
      $snapshot_status = $this->getSnapshotStatus();
      if ($snapshot_status == 'RUNNING') {
        return sprintf(E::ts('Snapshot [%d] is already processing!'), $snapshot_id);
      }
      elseif ($snapshot_status == 'COMPLETE') {
        return sprintf(E::ts('Snapshot [%d] is already completed!'), $snapshot_id);
      }
    }

    return FALSE;
  }

  /**
   * Will start a new test run, making sure that everything is clean
   */
  public function resetTestRun() {
    $this->snapshot->resetTestRun();
  }

  /**
   * read and load all exporters
   */
  public function getExporters() {
    if ($this->_exporters !== NULL) {
      return $this->_exporters;
    }
    $exporters = [];

    if (!empty($this->parameters['exporters'])) {
      $exporter_names = explode(',', $this->parameters['exporters']);
      foreach ($exporter_names as $exporter_name) {
        // init exporter
        $exporter_class = CRM_Donrec_Logic_Exporter::getClassForExporter($exporter_name);
        $exporter = new $exporter_class();
        $exporter->init($this);
        $exporters[] = $exporter;
      }
    }

    $this->_exporters = $exporters;
    return $exporters;
  }

  /**
   * execute the next step of a donation receipt run
   *
   * @return array of stats
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.MaxExceeded
   */
  public function nextStep() {
  // phpcs:enable
    // some containers
    $exporter_results = [];
    $files = [];
    $profile = $this->snapshot->getProfile();

    // Synchronize this step
    $lock = CRM_Utils_DonrecHelper::getLock('CRM_Donrec_Logic_Engine', 'nextStep');
    if (!$lock->isAcquired()) {
      // lock timed out
      Civi::log()->debug("de.systopia.donrec - couldn't acquire lock. Timeout is " . $lock->_timeout);

      // compile and return "state of affairs" report
      $stats = $this->createStats();
      $stats['log'] = [];
      $stats['files'] = $files;
      $stats['chunk_size'] = 0;
      CRM_Donrec_Logic_Exporter::addLogEntry(
        $stats,
        "Couldn't acquire lock. Parallel processing denied. Lock timeout is {$lock->_timeout}s."
      );
      return $stats;
    }

    // check status
    $is_bulk = !empty($this->parameters['bulk']);
    $is_test = !empty($this->parameters['test']);

    // initialize stuff
    $chunk = $this->snapshot->getNextChunk($is_bulk, $is_test);
    $exporters = $this->getExporters();

    // loop over receipts
    foreach ($chunk ?? [] as $chunk_id => $chunk_items) {

      // Setup some parameters
      //**********************************
      // Prepare chunk_items:
      // #FIXME: It is more convenient to have a similar array-structure for bulk-
      // and single-processing. In future the getNextChunk-method might be
      // refactored and build up the arrays correspondingly.
      $chunk_items = ($is_bulk) ? $chunk_items : [$chunk_items];
      $contact_id = (int) $chunk_items[0]['contact_id'];
      $line_ids = [];
      foreach ($chunk_items as $chunk_item) {
        $line_ids[] = $chunk_item['id'];
      }

      // create a SnapshotReceipt
      $snapshot_receipt = $this->snapshot->getSnapshotReceipt($line_ids, $is_test);

      // call exporters
      //**********************************
      $exporters_id = [];
      foreach ($exporters as $exporter) {

        $exporter_id = $exporter->getID();
        $exporters_id[] = $exporter_id;

        if ($is_bulk) {
          $result = $exporter->exportBulk($snapshot_receipt, $is_test);
        }
        else {
          $result = $exporter->exportSingle($snapshot_receipt, $is_test);
        }

        if (!isset($exporter_results[$exporter_id])) {
          $exporter_results[$exporter_id] = [];
          $exporter_results[$exporter_id]['success'] = 0;
          $exporter_results[$exporter_id]['failure'] = 0;
        }
        if ($result) {
          $exporter_results[$exporter_id]['success']++;
        }
        else {
          $exporter_results[$exporter_id]['failure']++;
        }
      }

      // save original pdfs and create receipt for non-test-runs
      //**********************************
      if (!$is_test) {
        $receipt_params = [];
        $receipt_params['exporters'] = implode(',', $exporters_id);
        $receipt_params['type'] = ($is_bulk) ? 'BULK' : 'SINGLE';

        if ($profile->saveOriginalPDF()) {
          $pdf_file = $this->getPDF($line_ids);
          $file = CRM_Donrec_Logic_File::createPermanentFile($pdf_file, basename($pdf_file), $contact_id);
          if (!empty($file)) {
            $receipt_params['original_file'] = $file['id'];
          }
        }
        CRM_Donrec_Logic_Receipt::createFromSnapshotReceipt($snapshot_receipt, $receipt_params);
      }
    }

    // The last chunk is empty.
    // If it is the last do some wrap-up.
    // Otherwise mark the chunk as processed.
    //**********************************
    $log_messages = [];
    if (!$chunk) {
      foreach ($exporters as $exporter) {
        $result = $exporter->wrapUp($this->snapshot->getId(), $is_test, $is_bulk);

        if (!empty($result['download_name']) && !empty($result['download_url'])) {
          $files[$exporter->getID()] = [$result['download_name'], $result['download_url']];
        }

        // collect appropriate error messages
        if (!empty($result['log'])) {
          foreach ($result['log'] as $log_entry) {
            if ($log_entry['type'] == 'ERROR'
               || $log_entry['type'] == 'FATAL'
               || ($is_test && $log_entry['type'] == 'INFO')) {
              $log_messages[] = $log_entry;
            }
          }
        }
      }
    }
    else {
      $this->snapshot->markChunkProcessed($chunk, $is_test, $is_bulk);
    }

    // compile stats
    //**********************************
    $stats = $this->createStats();
    $stats['log'] = $log_messages;

    // create final log-message
    foreach ($exporter_results as $exporter_id => $result) {
      $msg = sprintf(
        '%s processed %d items - %d succeeded, %d failed',
        $exporter_id,
        count($chunk ?? []),
        $result['success'],
        $result['failure']
      );
      $type = ($result['failure']) ? 'ERROR' : 'INFO';
      CRM_Donrec_Logic_Exporter::addLogEntry($stats, $msg, $type);
    }
    $stats['files'] = $files;
    if ($chunk == NULL) {
      $stats['progress'] = 100.0;
    }
    else {
      $stats['chunk_size'] = count($chunk);
    }

    // release our lock
    $lock->release();

    return $stats;
  }

  /**
   * simply check, if this run is -by parameter- a test run
   */
  public function isTestRun() {
    return !empty($this->parameters['test']);
  }

  /**
   * check what state the snapshot is in
   *
   * @param array|null $states
   * @return string
   *   possible results:
   *   'INIT':     freshly created snapshot
   *   'TESTING':  there is a test ongoing
   *   'TESTED':   there was a test and it's complete
   *   'RUNNING':  the process is still ongoing
   *   'COMPLETE': the process is complete
   */
  public function getSnapshotStatus($states = NULL) {
    $total_count = 0;
    if ($states == NULL) {
      $states = $this->snapshot->getStates();
    }
    foreach ($states as $state => $count) {
      $total_count += $count;
    }

    if ($states['NULL'] == $total_count) {
      return 'INIT';
    }
    if ($states['DONE'] > 0) {
      if ($states['DONE'] == $total_count) {
        return 'COMPLETE';
      }
      else {
        return 'RUNNING';
      }
    }
    else {
      if ($states['TEST'] == $total_count) {
        return 'TESTED';
      }
      else {
        return 'TESTING';
      }
    }
  }

  /**
   * generates a set of statistics on the current state
   *
   * @return array
   *   'progress'  float value [0..100] giving the progress in %
   *            'status'    @see getSnapshotStatus()
   *            etc.
   */
  public function createStats() {
    $stats = [];
    $states = $this->snapshot->getStates();

    // TODO: Implement settings or dynamics
    // the other 20% are wrap up
    $chunk_proportion = 80.0;

    $stats['count'] = 0;
    foreach ($states as $state => $count) {
      $stats['count'] += $count;
    }
    $stats['completed_test'] = $states['TEST'];
    $stats['progress_test'] = $stats['completed_test'] * $chunk_proportion / (float) $stats['count'];
    $stats['completed_real'] = $states['DONE'];
    $stats['progress_real'] = $stats['completed_real'] * $chunk_proportion / (float) $stats['count'];

    $mode = ($this->isTestRun()) ? 'test' : 'real';
    $stats['mode'] = $mode;
    $stats['completed'] = $stats['completed_' . $mode];
    $stats['progress'] = $stats['progress_' . $mode];

    $stats['status'] = $this->getSnapshotStatus();
    return $stats;
  }

  /**
   * get the retained snapshot object
   */
  public function getSnapshot() {
    return $this->snapshot;
  }

  /**
   * get or create a pdf file for the snapshot line
   * @param list<int> $snapshot_line_ids
   * @return string|FALSE
   */
  public function getPDF($snapshot_line_ids) {
    // get the proc-info for only one of the snapshot-lines
    // should be the same for all others
    $proc_info = $this->snapshot->getProcessInformation($snapshot_line_ids[0]);

    // was a pdf already created?
    if (isset($proc_info['PDF']['pdf_file'])) {
      /** @var string $filename */
      $filename = $proc_info['PDF']['pdf_file'];

      // otherwise create a new one
    }
    else {
      // get snapshot-receipt and tokens
      $snapshot_receipt = $this->snapshot->getSnapshotReceipt($snapshot_line_ids, FALSE);
      $tokens = $snapshot_receipt->getAllTokens();
      // get template and create pdf
      $tpl_param = [];
      $profile = $this->snapshot->getProfile();
      $template = $profile->getTemplate();
      $filename = $template->generatePDF($tokens, $tpl_param, $profile);
    }

    return $filename;
  }

  /**
   * get or create a pdf file for the snapshot line
   * @param int $snapshot_line_id
   * @param string $file
   */
  public function setPDF($snapshot_line_id, $file) {
    $proc_info = $this->snapshot->getProcessInformation($snapshot_line_id);
    $proc_info['PDF']['pdf_file'] = $file;
    $this->snapshot->updateProcessInformation($snapshot_line_id, $proc_info);
  }

}
