<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class represents a single snapshot
 */
class CRM_Donrec_Logic_Snapshot {
  // unique snapshot id
  private $Id;

  /** cache value for the snapshot's profile */
  private $_profile = NULL;

  // these fields of the table get copied into the chunk
  private static $CHUNK_FIELDS = array('id', 'contribution_id', 'contact_id', 'financial_type_id', 'status', 'created_by', 'total_amount', 'non_deductible_amount', 'currency', 'receive_date', 'contact_id', 'date_from', 'date_to', 'profile');
  private static $CONTACT_FIELDS = array('contact_id','display_name', 'street_address', 'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3', 'postal_code', 'city', 'country');
  private static $LINE_FIELDS = array('id', 'contribution_id', 'contact_id', 'financial_type_id', 'status', 'created_by', 'created_timestamp', 'total_amount', 'non_deductible_amount', 'currency', 'receive_date', 'date_from', 'date_to', 'profile');
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
  *                zero (-1/1: one day expired, -2/2: two
  *                days etc.)
  * @return array(
  *      'snapshot' => snapshot-object or NULL,
  *      'intersection_error' => intersection-error-object or NULL
  *      )
  */
  public static function create(&$contributions, $creator_id, $date_from, $date_to, $profile_name, $expired = 0) {

    $return = array(
      'snapshot' => NULL,
      'intersection_error' => NULL,
    );

    //TODO: special handling for this case?
    $error = self::hasIntersections();
    if ($error) {
      $return['intersection_error'] = $error;
      return $return;
    }

    if (count($contributions) < 1) {
      return $return;
    }

    // get next snapshot id
    // FIXME: this might cause race conditions
    $new_snapshot_id = (int)CRM_Core_DAO::singleValueQuery("SELECT max(`snapshot_id`) FROM `donrec_snapshot`;");
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
          "INSERT INTO `donrec_snapshot` (
              `id`,
              `snapshot_id`,
              `profile`,
              `contribution_id`,
              `contact_id`,
              `financial_type_id`,
              `created_timestamp`,
              `expires_timestamp`,
              `status`,
              `created_by`,
              `total_amount`,
              `non_deductible_amount`,
              `currency`,
              `receive_date`,
              `date_from`,
              `date_to`)
          SELECT
              NULL,
              %1 as `snapshot_id`,
              %2 as `profile`,
              `id` as `contribution_id`,
              `contact_id`,
              `financial_type_id`,
              NOW() as `created_timestamp`,
              NOW() $operator as `expires_timestamp`,
              NULL,
              %3,
              `total_amount`,
              `non_deductible_amount`,
              `currency`,
              `receive_date`,
              '$date_from' as `date_from`,
              '$date_to' as `date_to`
          FROM
              `civicrm_contribution`
          WHERE
              `id` IN ($id_string)
              ;";
    // FIXME: do not include contributions with valued issued don. rec.

    // prepare parameters
    $params = array(
                1 => array($new_snapshot_id, 'Integer'),
                2 => array($profile_name, 'String'),
                3 => array($creator_id, 'Integer'));

    // execute the query
    $result = CRM_Core_DAO::executeQuery($insert_query, $params);
    $return['snapshot'] = new self($new_snapshot_id);

    // now check for conflicts with other snapshots
    $error = self::hasIntersections($new_snapshot_id);
    if ($error) {
      $return['intersection_error'] = $error;
      // this snapshot conflicts with others, delete
      // TODO: error handling
      //$snapshot->delete();
      //return NULL;
      return $return;
    } else {
      return $return;
    }
  }

  /**
  * deletes the snapshot (permanently on database level!)
  */
  public function delete() {
    return (bool)CRM_Core_DAO::singleValueQuery(
      "DELETE FROM `donrec_snapshot`
       WHERE `snapshot_id` = %1;", array(1 => array($this->Id, 'Integer')));
  }

  /**
   * checks if the snapshot exists, i.e. if there is at least one item
   */
  private function exists() {
    return (bool)CRM_Core_DAO::singleValueQuery(
      "SELECT EXISTS(SELECT 1 FROM `donrec_snapshot`
       WHERE `snapshot_id` = %1);", array(1 => array($this->Id, 'Integer')));
  }

  /**
   * get the snapshot's creator (a contact_id)
   */
  public function getCreator() {
    return (int) CRM_Core_DAO::singleValueQuery(
      "SELECT `created_by` FROM `donrec_snapshot`
       WHERE `snapshot_id` = %1 LIMIT 1;", array(1 => array($this->Id, 'Integer')));
  }

  /**
   * will select a previously unprocessed set of snapshot items
   *
   * @return array: <id> => array with values
   */
  public function getNextChunk($is_bulk, $is_test) {
    $chunk_size = CRM_Donrec_Logic_Settings::getChunkSize();
    $snapshot_id = $this->getId();
    $chunk = array();
    if ($is_test) {
      $status_clause = "`status` IS NULL";
    } else {
      $status_clause = "(`status` IS NULL OR `status`='TEST')";
    }

    // here, we need a different algorithm for bulk than for single:
    if (empty($is_bulk)) {
      // SINGLE case: just grab $chunk_size items
      $query = "SELECT * FROM `donrec_snapshot` WHERE `snapshot_id` = $snapshot_id AND $status_clause LIMIT $chunk_size;";
      $result = CRM_Core_DAO::executeQuery($query);
      while ($result->fetch()) {
        $chunk_line = array();
        foreach (self::$CHUNK_FIELDS as $field) {
          $chunk_line[$field] = $result->$field;
        }
        $chunk[$chunk_line['id']] = $chunk_line;
      }
    } else {
      // BULK case: get items grouped by contact ID until it exceeds $chunk_size

      // get all lines
      $query = "SELECT
                 snapshot.*,
                 a.contrib_count
                FROM
                `donrec_snapshot` AS snapshot,
                (SELECT `contact_id`, COUNT(*) AS contrib_count
                  FROM `donrec_snapshot`
                  GROUP BY `contact_id`) AS a
                WHERE a.`contact_id` = snapshot.`contact_id`
                AND snapshot.`snapshot_id` = $snapshot_id
                AND $status_clause
                ORDER BY snapshot.`contact_id` ASC;";
      $query = CRM_Core_DAO::executeQuery($query);

      $last_added_contact_id = NULL;
      $contribution_count = 0;
      while ($query->fetch()) {
        if ($last_added_contact_id != $query->contact_id) {
          // this is a new contact ID
          if ( (count($chunk) >= $chunk_size) || ($contribution_count > 5 * $chunk_size ) ) {
            // we already have $chunk_size contacts, or 5x $chunk_size contributions
            //  => that's enough for this chunk!
            break;
          }

          // ok, we're still under the limit => create a section for the contact
          $chunk[$query->contact_id] = array();
          $last_added_contact_id = $query->contact_id;
        }

        // add contribution
        $contribution = array();
        foreach (self::$CHUNK_FIELDS as $field) {
          $contribution[$field] = $query->$field;
        }
        $chunk[$query->contact_id][] = $contribution;
        $contribution_count += 1;
      }
    }

    // reset the process information for the given chunk
    $this->resetChunk($chunk, $is_bulk);

    if (count($chunk)==0) {
      return NULL;
    } else {
      return $chunk;
    }
  }

  /**
   * reset the process information for the given chunk
   */
  public function resetChunk($chunk, $is_bulk) {
    if ($chunk==NULL) return;

    if ($is_bulk) {
      // get all second level ids
      $ids = array();
      foreach($chunk as $ck => $cv) {
        foreach ($cv as $lk => $lv) {
          array_push($ids, $lv['id']);
        }
      }
    } else {
      $ids = array_keys($chunk);
    }

    if (empty($ids)) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: invalid chunk detected!');
    } else {
      $ids_str = implode(',', $ids);

      // reset process information for all IDs
      $query = "UPDATE `donrec_snapshot` SET `process_data` = NULL WHERE `id` IN ($ids_str);";
      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
  * will mark a chunk as produced by getNextChunk() as being processed
  */
  public function markChunkProcessed($chunk, $is_test, $is_bulk=FALSE) {
    if ($chunk==NULL) return;

    $new_status = $is_test?'TEST':'DONE';
    if (!$is_bulk) {
      $ids = array_keys($chunk);
    }else{
      // get all second level ids
      $ids = array();
      foreach($chunk as $ck => $cv) {
          foreach ($cv as $lk => $lv) {
            array_push($ids, $lv['id']);
          }
      }
    }

    if (empty($ids)) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: invalid chunk detected!');
    } else {
      $ids_str = implode(',', $ids);

      // update process-info-field
      $proc_info['is_bulk'] = $is_bulk;
      foreach ($ids as $id) {
        $this->updateProcessInformation($id, $proc_info);
      }

      // update status-field
      $query = "UPDATE `donrec_snapshot` SET `status`='$new_status' WHERE `id` IN ($ids_str);";
      CRM_Core_DAO::executeQuery($query);
      // CRM_Core_Error::debug_log_message("de.systopia.donrec: lines $ids are now processed ($query)");
    }
  }


  /**
  * get the snapshot's state distribution
  *
  * @return an array <state> => <count>
  */
  public function getStates() {
    $states = array('NULL' => 0, 'TEST' => 0, 'DONE' => 0);
    $id = $this->Id;
    $query = "
      SELECT COUNT(`id`) AS count, `status` AS status
      FROM `donrec_snapshot`
      WHERE `snapshot_id` = $id GROUP BY `status`";
    $result = CRM_Core_DAO::executeQuery($query);
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
      "UPDATE `donrec_snapshot`
       SET `status`=NULL, `process_data`=NULL
       WHERE `status`='TEST';");
  }

  /**
  * deletes expired snapshots (permanently on database level!)
  */
  public static function cleanup() {
    CRM_Core_DAO::singleValueQuery(
      "DELETE FROM `donrec_snapshot`
       WHERE `expires_timestamp` < NOW();");
  }

  /**
  * checks whether there are intersections in snapshots
  * @return zero when no error occured,
  */
  public static function hasIntersections($snapshot_id = 0) {
    // TODO: speed up by looking at one particular snapshot ?
    // We do not check snapshots with status DONE: If we delete a receipt but
    // the snapshot still exists, we get an intersection-error on trying to
    // produce the receipt again.
    $query =   "
      SELECT original.`snapshot_id`, contact.`display_name`, original.`expires_timestamp`
      FROM `donrec_snapshot` AS original
      INNER JOIN `donrec_snapshot` AS copy ON original.`contribution_id` = copy.`contribution_id`
      AND (original.`status` != 'DONE' OR original.`status` IS NULL)
      AND (copy.`status` != 'DONE' OR copy.`status` IS NULL)
      LEFT JOIN `civicrm_contact` AS contact ON copy.`created_by` = contact.`id`
      WHERE original.`snapshot_id` <> copy.`snapshot_id`
      GROUP BY `snapshot_id`;";

    $results = CRM_Core_DAO::executeQuery($query);
    $intersections = array($snapshot_id);

    while ($results->fetch()) {
      $intersections[] = array($results->snapshot_id, $results->display_name, $results->expires_timestamp);
    }

    if (count($intersections) > 1) {
      return $intersections;
    }else{
      return FALSE;
    }
  }

  // --- HELPER/GETTER/SETTER METHODS ---

  public function getId() {
    return $this->Id;
  }

  public function getIds() {
    $snapshot_id = $this->Id;
    $query = "SELECT `id` FROM `donrec_snapshot` WHERE `snapshot_id` = $snapshot_id;";
    $result = CRM_Core_DAO::executeQuery($query);
    $ids = array();
    while ($result->fetch()) {
      $ids[] = $result->id;
    }
    return $ids;
  }

  /**
  * reads and parses the JSON process information field
  */
  public function getProcessInformation($snapshot_item_id) {
    $item_id = (int) $snapshot_item_id;
    if (!$item_id) return array();

    // read value
    $raw_value = CRM_Core_DAO::singleValueQuery(
      "SELECT `process_data` FROM `donrec_snapshot` WHERE `id` = $item_id;");
    if (empty($raw_value)) return array();

    $value = json_decode($raw_value, TRUE);
    if ($value==NULL) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: warning, cannot decode process_data of ID $item_id!");
      return array();
    } else {
      return $value;
    }
  }

  /**
  * sets the JSON process information field
  */
  public function setProcessInformation($snapshot_item_id, $value) {
    $item_id = (int) $snapshot_item_id;
    if (!$item_id) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: invalid snapshot id detected! ($item_id)");
      return;
    }

    $raw_value = json_encode($value);
    if ($raw_value==FALSE) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: warning, cannot encode process_data for ID $item_id!");
    } else {
      return (bool) CRM_Core_DAO::singleValueQuery(
        "UPDATE `donrec_snapshot`
         SET `process_data` = %1
         WHERE `id` = %2;",
        array(1 => array($raw_value, 'String'), 2 => array($item_id, 'Integer')));
    }
  }

  /**
  * updates the JSON process information field
  */
  public function updateProcessInformation($snapshot_item_id, $array) {
    $infos = $this->getProcessInformation($snapshot_item_id);
    $merged_infos = array_merge($infos, $array);
    $this->setProcessInformation($snapshot_item_id, $merged_infos);
  }

  /**
  * Returns a line of this snapshot
  * @param int line id
  * @return array or empty array
  */
  public function getLine($line_id) {
    $snapshot_id = $this->Id;
    $query = "SELECT * FROM `donrec_snapshot` WHERE `snapshot_id` = $snapshot_id AND id = %1 LIMIT 1;";
    $params = array(1 => array($line_id, 'Integer'));
    $result = CRM_Core_DAO::executeQuery($query, $params);
    $result->fetch();
    $line = array();
    foreach (self::$LINE_FIELDS as $field) {
      $line[$field] = $result->$field;
    }
    return $line;
  }

  /**
  * Checks if a snapshot is marked as bulk or single
  * @return string bulk|single or null
  */
  // TODO: refactor this. Process-infos are already accessed in getExporters.
  // Use a common method to not fetch process-infos twice.
  public static function singleOrBulk($id) {
    $query = "
      SELECT `process_data`
      FROM donrec_snapshot
      WHERE `process_data` IS NOT NULL
        AND `snapshot_id` = $id
      LIMIT 1";
    $raw_value = CRM_Core_DAO::singleValueQuery($query);

    // no process_data set: abort
    if (!$raw_value) {
      return;
    }
    // decode process-data
    $info = json_decode($raw_value, TRUE);

    // if it could not be decoded: abort
    if ($info==NULL) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec: warning, cannot decode process_data!");
      return;
    }

    // is_bulk was not set: abort
    if (!array_key_exists('is_bulk', $info)) {
      return;
    } elseif ($info['is_bulk']) {
      return 'bulk';
    } else {
      return 'single';
    }
  }

  /**
  * Returns an array all exporters used to process the snapshot
  * @return array
  */
  public function getExporters() {
    // get snapshot id
    $id = $this->getId();

    // get ids of items which are already processed
    $query = "
      SELECT `id`
      FROM `donrec_snapshot`
      WHERE snapshot_id = $id
      AND status = 'DONE'
    ";
    $result = CRM_Core_DAO::executeQuery($query);

    // merge process-data of all items into array
    $merged_process_data = array();
    $count = 0;
    while ($result->fetch()) {
      $process_data = $this->getProcessInformation($result->id);
      $merged_process_data = array_merge($merged_process_data, $process_data);
      $count += 1;
    }

    // exclude values that aren't exporters
    $list_keys = array_keys($merged_process_data);
    $exporters = array_intersect($list_keys, CRM_Donrec_Logic_Exporter::listExporters());

    // if we have processed lines but no exporter, we use the Dummy
    if (!empty($count) && empty($exporters)) {
      $exporters = array('Dummy');
    }
    return $exporters;
  }

  /**
  * Returns an array with statistic values of the snapshot
  * @return array
  */
  public static function getStatistic($id) {
    $query1 = "SELECT
      COUNT(*) AS contribution_count,
      SUM(total_amount) AS total_amount,
      created_timestamp AS creation_date,
      date_from AS date_from,
      date_to AS date_to,
      currency
      FROM donrec_snapshot
      WHERE snapshot_id = $id";

    $query2 = "SELECT COUNT(*)
      FROM (
        SELECT contact_id
        FROM donrec_snapshot
        WHERE snapshot_id = $id
        GROUP BY contact_id
      ) A";
    $result1 = CRM_Core_DAO::executeQuery($query1);
    $result1->fetch();

    // get status of the snapshot
    // TODO: we need to create a snapshot-object because getStates is not a
    // static function. Therefore it would make sense to rewrite getStatistic
    // as a object-method as well.
    $snapshot = self::get($id);
    $states = $snapshot->getStates();
    // if we have TEST- and DONE-states we have a problem
    if ($states['TEST'] && $states['DONE']) {
      CRM_Core_Error::debug_log_message("de.systopia.donrec - snapshot with id $id has entries with both TEST and DONE states!");
      // TODO : raise an error
    } elseif ($states['TEST']) {
      $status = 'TEST';
    } elseif ($states['DONE']) {
      $status = 'DONE';
    } else {
      $status = null;
    }

    $statistic = array(
      'id' => $id,
      'contact_count' => (int) CRM_Core_DAO::singleValueQuery($query2),
      'contribution_count' => $result1->contribution_count,
      'total_amount' => $result1->total_amount,
      'creation_date' => $result1->creation_date,
      'date_from' => $result1->date_from,
      'date_to' => $result1->date_to,
      'status' => $status,
      'singleOrBulk' => self::singleOrBulk($id),
      'exporters' => $snapshot->getExporters($id),
      'currency' => $result1->currency
    );
    return $statistic;
  }

  /**
  * Returns an array with ids of already existing snapshots of a specific
  * user.
  * @return array
  */
  public static function getUserSnapshots($creator_id) {
    $remaining_snapshots = array();

    $query = "
      SELECT snapshot_id
      FROM donrec_snapshot
      WHERE (status IS NULL OR status != 'DONE')
      AND created_by = $creator_id
      GROUP BY snapshot_id";

    $result = CRM_Core_DAO::executeQuery($query);
    while ($result->fetch()) {
      array_push($remaining_snapshots, $result->snapshot_id);
    }
    return $remaining_snapshots;
  }

  /**
  * Deletes all not processed snapshots of a given user.
  * @return return-value from CRM_Core_DAO::executeQuery()
  */
  public static function deleteUserSnapshots($creator_id) {
    $remaining_snapshots = array();

    $query = "
      DELETE
      FROM donrec_snapshot
      WHERE (status IS NULL OR status != 'DONE')
      AND created_by = $creator_id";

    $result = CRM_Core_DAO::executeQuery($query);
    return $result;
  }

  /**
  * Checks if there is a snapshot-entry for a non-processed snapshot for
  * a given contribution.
  * @return boolean
  */
  public static function isInOpenSnapshot($contribution_id) {
    // do a cleanup here (ticket #1616)
    self::cleanup();

    // TODO: what if status is DONE, but the snapshot is not finished yet?
    $query = "
      SELECT COUNT(*)
      FROM `donrec_snapshot`
      WHERE contribution_id = $contribution_id
      AND (status IS NULL OR status != 'DONE')
    ";
    return (bool) CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * create a set of CRM_Donrec_Logic_SnapshotReceipt objects with a given chunk
   *
   * @return an array of CRM_Donrec_Logic_SnapshotReceipts
   */
   // FIXME: This function seems to be superfluous. Now where used anymore
  public function getSnapshotReceipts($chunk, $is_bulk, $is_test) {
    $temp_receipts = array();
    if ($is_bulk) {
      // then create the temporary receipts
      foreach ($chunk as $contact_id => $snapshot_lines) {
        $temp_receipts[] = new CRM_Donrec_Logic_SnapshotReceipt($this, $snapshot_lines, $is_test);
      }
    } else {
      // create individual receipts
      foreach ($chunk as $snapshot_line) {
        $temp_receipts[] = new CRM_Donrec_Logic_SnapshotReceipt($this, array($snapshot_line), $is_test);
      }
    }
    return $temp_receipts;
  }

  /**
   * Get a CRM_Donrec_Logic_SnapshotReceipt object
   * @param list of snapshot-line-ids
   * @param boolean is_test
   * @return CRM_Donrec_Logic_SnapshotReceipt
   */
  public function getSnapshotReceipt($snapshot_line_ids, $is_test) {
    foreach($snapshot_line_ids as $id) {
      $lines[] = $this->getLine($id);
    }
    return new CRM_Donrec_Logic_SnapshotReceipt($this, $lines, $is_test);
  }

  /**
   * Get the profile connected to this snapshot
   */
  public function getProfile() {
    if ($this->_profile == NULL) {
      $profile_name = CRM_Core_DAO::singleValueQuery(
        "SELECT profile FROM donrec_snapshot WHERE snapshot_id = %1 LIMIT 1;",
        array(1 => array($this->Id, 'Integer')));
      $this->_profile = CRM_Donrec_Logic_Profile::getProfile($profile_name, TRUE);
    }
    return $this->_profile;
  }
}
