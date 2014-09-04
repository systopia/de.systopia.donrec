<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This class represents a single snapshot
 */
class CRM_Donrec_Logic_Snapshot {
	// unique snapshot id
	private $Id;

	// private constructor to prevent
	// external instantiation
	private function __construct($id) {
		$this->Id = $id;
	} 

   /**
   * creates and returns a new snapshot object from the
   * given parameters
   *
   * @param $contributions        array of contribution ids that should
   *                              be part of the snapshot
   * @param $originator_id        civicrm id of the contact which creates
   *                              the snapshot
   * @param $expired              just for debugging purposes: creates an
   *                              expired snapshot if less/greater than 
   *							  zero (-1/1: one day expired, -2/2: two 
   *							  days etc.)
   * @return snapshot object OR NULL
   */
	public static function create(&$contributions, $originator_id, $expired = 0) {
		self::hasIntersections();

		if (count($contributions) < 1) {
			return NULL;
		}

		// get next snapshot id
		// FIXME: this might cause race conditions
		$new_snapshot_id = (int)CRM_Core_DAO::singleValueQuery("SELECT max(`snapshot_id`) FROM `civicrm_zwb_snapshot`;");
		$new_snapshot_id++;

		// build id string from contribution array
		$id_string = implode(', ', $contributions);

		// debugging/testing
		$operator = "+ INTERVAL 1 DAY";
		if ($expired != 0) {
			$operator = "- INTERVAL " . abs($expired) . " DAY";
		}

		// assemble the query
		$insert_query = 
					"INSERT INTO 
							`civicrm_zwb_snapshot` (
							`id`,
							`snapshot_id`,
							`contribution_id`, 
							`created_timestamp`, 
							`expires_timestamp`, 
							`status`, 
							`created_by`, 
							`total_amount`, 
							`non_deductible_amount`, 
							`currency`, 
							`receive_date`) 
					SELECT 
							NULL as `id`,
							'%1' as `snapshot_id`,
							`id`,
							NOW() as `created_timestamp`, 
							NOW() $operator as `expires_timestamp`,
							`contribution_status_id`,
							'%2' as `created_by`,
							`total_amount`,
							`non_deductible_amount`,
							`currency`,
							`receive_date`
					FROM
							`civicrm_contribution`
					WHERE
							`id` IN ($id_string)
							;";
		// FIXME: do not copy invalid contributions

		// prepare parameters 
		$params = array(1 => array($new_snapshot_id, 'Integer'),
						2 => array($originator_id, 'Integer'));

		// execute the query
		$result = CRM_Core_DAO::executeQuery($insert_query, $params);

		self::hasIntersections();

		// return a new snapshot object
		return new self($new_snapshot_id);
	}

   /**
   * checks whether the given contribution is part of a snapshot
   *
   * @param $contribution_id
   * @return snapshot_id OR NULL
   */
	public static function query($contribution_id) {
		self::cleanup();
		return (bool)CRM_Core_DAO::singleValueQuery(
			"SELECT `snapshot_id` 
			   FROM `civicrm_zwb_snapshot` 
			  WHERE `contribution_id` = %1;", array(1 => array($contribution_id, 'Integer')));
	}

   /**
   * deletes the snapshot (permanently on database level!)
   */
	public function delete() {
		return (bool)CRM_Core_DAO::singleValueQuery(
			"DELETE FROM `civicrm_zwb_snapshot` 
			 WHERE `snapshot_id` = %1;", array(1 => array($this->Id, 'Integer')));
	}

   /**
   * deletes expired snapshots (permanently on database level!)
   */
	public static function cleanup() {
		CRM_Core_DAO::singleValueQuery(
			"DELETE FROM `civicrm_zwb_snapshot` 
			 WHERE `expires_timestamp` < NOW();");
	}

   /**
   * checks whether there are intersections in snapshots
   * @return zero when no error occured, 
   */
	public static function hasIntersections() {
		$query = "SELECT  
						   `contribution_id`,
						   COUNT(*) 
				  FROM     `civicrm_zwb_snapshot` 
				  GROUP BY `contribution_id` HAVING COUNT(*) > 1;";
		$results = CRM_Core_DAO::executeQuery($query);
		$intersections = array();

		while ($results->fetch()) {
			$cid = $results->contribution_id;
			error_log("de.systopia.donrec: warning: snapshot conflict - contribution #$cid exists in multiple snapshots!");
			$intersections[] = $cid;
		}
		
		return count($intersections);
	}

	// --- HELPER/GETTER/SETTER METHODS ---

	public function getId() {
		return $this->Id;
	}
}