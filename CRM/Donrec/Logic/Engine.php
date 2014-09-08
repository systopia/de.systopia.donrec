<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This class represents the engine for donation receipt runs
 */
class CRM_Donrec_Logic_Engine {
	
	/**
	 * stores the related snapshot object
	 */
	protected $snapshot = NULL;

	/**
	 * stores the parameters as given by the user
	 * 
	 * known parameters:
	 *  exporters  array(exporter_classes)
	 *  bulk       1 or 0 - if 1, accumulative (='bulk') donation receipts should be issued
	 *  test       1 or 0 - if 0, the contributions will not actually be marked as 'reciept_issued'
	 */
	protected $parameters = array();

	/**
	 * Will try to initialize the engine with a snapshot 
	 * and the given parameters. If anything is wrong,
	 * an error message will be returned.
	 *
	 * @return string with an error message on fail, FALSE otherwise
	 */
	public function init($snapshot_id, $params=array()) {
		$this->parameters = $params;
		$this->snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
		if ($this->snapshot==NULL) {
			return sprintf(ts("Snapshot [%d] does not exist (any more)!"), $snapshot_id);
		}

		// now, check if it's ours:
		$user_id = CRM_Core_Session::singleton()->get('userID');
		$snapshot_creator_id = $this->snapshot->getCreator();
		if ($user_id != $snapshot_creator_id) {
			// load creator name
			$creator = civicrm_api3('Contact', 'getsingle', array('id' => $snapshot_creator_id));
			return sprintf(ts("Snapshot [%d] belongs to user '%s'[%s]!"), $snapshot_id, $creator['display_name'], $snapshot_creator_id);
		}

		// now, if this is supposed to be test mode, there must not be a real status
		if ($this->isTestRun()) {
			$snapshot_status = $this->getSnapshotStatus();
			if ($snapshot_status=='RUNNING') {
				return sprintf(ts("Snapshot [%d] is already processing!"), $snapshot_id);
			} elseif ($snapshot_status=='COMPLETE') {
				return sprintf(ts("Snapshot [%d] is already completed!"), $snapshot_id);
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
	 * start/continue an export run
	 */
	public function nextStep() {
		// TODO:
		usleep(100);
	}

	public function isTestRun() {
		return !empty($this->parameters['test']);
	}

	/**
	 * check what state the snapshot is in
	 * 
	 * @return possible results:
	 *  'INIT':     freshly created snapshot
	 *  'TESTING':  there is a test ongoing
	 *  'TESTED':   there was a test and it's complete
	 *  'RUNNING':  the process is still ongoing
	 *  'COMPLETE': the process is complete
	 */
	public function getSnapshotStatus() {
		$total_count = 0;
		$states = $this->snapshot->getStates();
		foreach ($states as $state => $count) {
			$total_count += $count;
		}

		if ($states['NULL'] == $total_count) return 'INIT';
		if ($states['DONE'] > 0) {
			if ($states['DONE'] == $total_count) {
				return 'COMPLETE';
			} else {
				return 'RUNNING';
			}
		} else {
			if ($states['TEST'] == $total_count) {
				return 'TESTED';
			} else {
				return 'TESTING';
			}
		}
	}
}