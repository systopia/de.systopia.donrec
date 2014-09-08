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

  // these fields of the table get copied into the chunk
  private static $CHUNK_FIELDS = array('id', 'contribution_id', 'status', 'created_by', 'total_amount', 'non_deductible_amount', 'currency', 'receive_date');

	// private constructor to prevent
	// external instantiation
	private function __construct($id) {
		$this->Id = $id;
	} 

	/**
	 * get an existing snapshot
	 */
	public static function get($snapshot_id) {
		$snapshot = new CRM_Donrec_Logic_Snapshot($snapshot_id);
		if ($snapshot->exists()) {
			return $snapshot;
		} else {
			return NULL;
		}
	}


   /**
   * creates and returns a new snapshot object from the
   * given parameters
   *
   * @param $contributions        array of contribution ids that should
   *                              be part of the snapshot
   * @param $creator_id           civicrm id of the contact which creates
   *                              the snapshot
   * @param $expired              just for debugging purposes: creates an
   *                              expired snapshot if less/greater than 
   *							  zero (-1/1: one day expired, -2/2: two 
   *							  days etc.)
   * @return snapshot object OR NULL
   */
	public static function create(&$contributions, $creator_id, $expired = 0) {
		self::hasIntersections();

		if (count($contributions) < 1) {
			return NULL;
		}

		// get next snapshot id
		// FIXME: this might cause race conditions
		$new_snapshot_id = (int)CRM_Core_DAO::singleValueQuery("SELECT max(`snapshot_id`) FROM `civicrm_donrec_snapshot`;");
		$new_snapshot_id++;

		// build id string from contribution array
		$id_string = implode(', ', $contributions);

		// debugging/testing
		$operator = "+ INTERVAL 1 DAY";
		if ($expired != 0) {
			$operator = "- INTERVAL " . abs($expired) . " DAY";
		}

		// assemble the query
    // remark: if you change this, also adapt the $CHUNK_FIELDS list
		$insert_query = 
					"INSERT INTO 
							`civicrm_donrec_snapshot` (
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
							NULL,
							'%2',
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
						2 => array($creator_id, 'Integer'));

		// execute the query
		$result = CRM_Core_DAO::executeQuery($insert_query, $params);
		$snapshot = new self($new_snapshot_id);

		// now check for conflicts with other snapshots
		if (self::hasIntersections($new_snapshot_id)) {
			// this snapshot conflicts with others, delete
			// TODO: error handling
			//$snapshot->delete();
			//return NULL;
      return $snapshot;
		} else {
			return $snapshot;
		}
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
			   FROM `civicrm_donrec_snapshot` 
			  WHERE `contribution_id` = %1;", array(1 => array($contribution_id, 'Integer')));
	}

   /**
   * deletes the snapshot (permanently on database level!)
   */
	public function delete() {
		return (bool)CRM_Core_DAO::singleValueQuery(
			"DELETE FROM `civicrm_donrec_snapshot` 
			 WHERE `snapshot_id` = %1;", array(1 => array($this->Id, 'Integer')));
	}

    /**
     * checks if the snapshot exists, i.e. if there is at least one item
     */
	private function exists() {
		return (bool)CRM_Core_DAO::singleValueQuery(
			"SELECT EXISTS(SELECT 1 FROM `civicrm_donrec_snapshot`
			 WHERE `snapshot_id` = %1);", array(1 => array($this->Id, 'Integer')));
	}

    /**
     * get the snapshot's creator (a contact_id)
     */
	public function getCreator() {
		return (int) CRM_Core_DAO::singleValueQuery(
			"SELECT `created_by` FROM `civicrm_donrec_snapshot` 
			 WHERE `snapshot_id` = %1 LIMIT 1;", array(1 => array($this->Id, 'Integer')));
	}

  /**
   * will select a previously unprocessed set of snapshot items
   *
   * @return array: <id> => array with values
   */
  public function getNextChunk($is_bulk, $is_test) {
    $chunk_size = 1;     // TODO: get from settings
    $snapshot_id = $this->getId();
    $chunk = array();
    if ($is_test) {
      $status_clause = "`status` IS NULL";
    } else {
      $status_clause = "(`status` IS NULL OR `status`='TEST')";
    }

    // here, we need a different algorithm for bulk than for single:
    if (!$is_bulk) {
      // SINGLE case: just grab $chunk_size items
      $query = CRM_Core_DAO::executeQuery(
          "SELECT * FROM `civicrm_donrec_snapshot` WHERE `snapshot_id` = $snapshot_id AND $status_clause LIMIT $chunk_size;");
      while ($query->fetch()) {
        $chunk_line = array();
        foreach (self::$CHUNK_FIELDS as $field) {
          $chunk_line[$field] = $query->$field;
        }
        $chunk[$chunk_line['id']] = $chunk_line;
      }
    } else {
      // BULK case: get items grouped by contact ID until exceeds $chunk_size
      // TODO:
    }

    if (count($chunk)==0) {
      return NULL;
    } else {
      return $chunk;
    }
  }

  /**
   * will mark a chunk as produced by getNextChunk() as being processed
   */
  public function markChunkProcessed($chunk, $is_test) {
    if ($chunk==NULL) return;

    $new_status = $is_test?'TEST':'DONE';
    $ids = implode(',', array_keys($chunk));
    if (empty($ids)) {
      error_log('de.systopia.donrec: invalid chunk detected!');
    } else {
      CRM_Core_DAO::executeQuery(
        "UPDATE `civicrm_donrec_snapshot` SET `status`='$new_status' WHERE `id` IN ($ids);");
    }
  }


    /**
     * get the snapshot's state distribution
     * 
     * @return an array <state> => <count>
     */
	public function getStates() {
		$states = array('NULL' => 0, 'TEST' => 0, 'DONE' => 0);
		$result = CRM_Core_DAO::executeQuery(
			"SELECT COUNT(`id`) AS count, `status` AS status 
			 FROM `civicrm_donrec_snapshot` 
			 WHERE `snapshot_id` = %1 GROUP BY `status`;", array(1 => array($this->Id, 'Integer')));
		while ($result->fetch()) {
			if ($result->status==NULL) {
				$states['NULL'] = $result->count;
			} else {
				$states[$result->status] = $result->count;
			}
		}
		return $states;
	}

	/**
	 * Will reset a test run
	 */
	public function resetTestRun() {
		CRM_Core_DAO::executeQuery(
			"UPDATE `civicrm_donrec_snapshot` 
			 SET `status`=NULL, `process_data`=NULL 
			 WHERE `status`='TEST';");
	}

   /**
   * deletes expired snapshots (permanently on database level!)
   */
	public static function cleanup() {
		CRM_Core_DAO::singleValueQuery(
			"DELETE FROM `civicrm_donrec_snapshot` 
			 WHERE `expires_timestamp` < NOW();");
	}

   /**
   * checks whether there are intersections in snapshots
   * @return zero when no error occured, 
   */
	public static function hasIntersections($snapshot_id = 0) {
		// TODO: speed up by looking at one particular snapshot ?
		$query = "SELECT  
						   `contribution_id`,
						   COUNT(*) 
				  FROM     `civicrm_donrec_snapshot` 
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