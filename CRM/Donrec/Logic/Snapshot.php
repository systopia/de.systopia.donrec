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
 * This class represents a single snapshot
 */
class CRM_Donrec_Logic_Snapshot {
  // unique snapshot id
  private int $Id;

  /**
   * cache value for the snapshot's profile */
  private ?CRM_Donrec_Logic_Profile $_profile = NULL;

  // these fields of the table get copied into the chunk
  private static array $CHUNK_FIELDS = [
    'id',
    'contribution_id',
    'contact_id',
    'financial_type_id',
    'status',
    'created_by',
    'total_amount',
    'non_deductible_amount',
    'currency',
    'receive_date',
    'contact_id',
    'date_from',
    'date_to',
    'profile_id',
  ];

  private static array $LINE_FIELDS = [
    'id',
    'contribution_id',
    'contact_id',
    'financial_type_id',
    'status',
    'created_by',
    'created_timestamp',
    'total_amount',
    'non_deductible_amount',
    'currency',
    'receive_date',
    'date_from',
    'date_to',
    'profile_id',
  ];
  // private constructor to prevent

  /**
   * external instantiation
   *
   * @param int $id
   */
  private function __construct($id) {
    $this->Id = (int) $id;
  }

  /**
   * get an existing snapshot
   *
   * @param int $snapshot_id
   *
   * @return \CRM_Donrec_Logic_Snapshot|null
   */
  public static function get($snapshot_id) {
    $snapshot = new CRM_Donrec_Logic_Snapshot($snapshot_id);
    if ($snapshot->exists()) {
      return $snapshot;
    }
    else {
      return NULL;
    }
  }

  /**
   * get the snapshot of a given line_id
   * @param int $snapshot_line_id
   * @return \CRM_Donrec_Logic_Snapshot|null
   */
  public static function getSnapshotForLineID($snapshot_line_id) {
    $snapshot_id = (int) CRM_Core_DAO::singleValueQuery('SELECT `snapshot_id` FROM `donrec_snapshot` WHERE `id` = %1;',
      [1 => [$snapshot_line_id, 'Integer']]);
    if ($snapshot_id) {
      // no need to check if it exists, b/c if not the query would be empty
      return new CRM_Donrec_Logic_Snapshot($snapshot_id);
    }
    else {
      return NULL;
    }
  }

  /**
   * creates and returns a new snapshot object from the
   * given parameters
   *
   * @param array $contributions        array of contribution ids that should
   *                              be part of the snapshot
   * @param int $creator_id           civicrm id of the contact which creates
   *                              the snapshot
   * @param string $date_from
   * @param string $date_to
   * @param int $profile_id
   * @param int $expired just for debugging purposes: creates an
   *                              expired snapshot if less/greater than
   *                zero (-1/1: one day expired, -2/2: two
   *                days etc.)
   * @return array (
   *   'snapshot' => snapshot-object or NULL,
   *      'intersection_error' => intersection-error-object or NULL
   *      )
   */
  public static function create(&$contributions, $creator_id, $date_from, $date_to, $profile_id, $expired = 0) {

    $return = [
      'snapshot' => NULL,
      'intersection_error' => NULL,
    ];

    //TODO: special handling for this case?
    $error = self::hasIntersections();
    if ($error) {
      $return['intersection_error'] = $error;
      return $return;
    }

    if (count($contributions) < 1) {
      return $return;
    }

    $enable_line_item = CRM_Donrec_Logic_Settings::get('donrec_enable_line_item');

    // get next snapshot id
    // FIXME: this might cause race conditions
    $new_snapshot_id = (int) CRM_Core_DAO::singleValueQuery('SELECT max(`snapshot_id`) FROM `donrec_snapshot`;');
    $new_snapshot_id++;
    $profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);

    // build id string from contribution array
    $id_string = implode(', ', $contributions);
    // Build financial type clause for financial type
    $financialTypeClause = $profile->getContributionTypesClause();

    // debugging/testing
    $operator = '+ INTERVAL 1 DAY';
    if ($expired != 0) {
      $operator = '- INTERVAL ' . abs($expired) . ' DAY';
    }

    // assemble the query
    // remark: if you change this, also adapt the $CHUNK_FIELDS list
    if ($enable_line_item) {
      $insert_query =
        "INSERT INTO `donrec_snapshot` (
              `id`,
              `snapshot_id`,
              `profile_id`,
              `contribution_id`,
              `line_item_id`,
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
              %2 as `profile_id`,
              `civicrm_contribution`.`id` as `contribution_id`,
              `civicrm_line_item`.`id` as `line_item_id`,
              `contact_id`,
              IF (`civicrm_line_item`.`id` IS NOT NULL, `civicrm_line_item`.`financial_type_id`,
                `civicrm_contribution`.`financial_type_id`),
              NOW() as `created_timestamp`,
              NOW() $operator as `expires_timestamp`,
              NULL,
              %3,
              IF (`civicrm_line_item`.`id` IS NOT NULL, `civicrm_line_item`.`line_total`,
                `civicrm_contribution`.`total_amount`),
              IF (`civicrm_line_item`.`id` IS NOT NULL, `civicrm_line_item`.`non_deductible_amount`,
                `civicrm_contribution`.`non_deductible_amount`),
              `currency`,
              `receive_date`,
              '$date_from' as `date_from`,
              '$date_to' as `date_to`
          FROM
              `civicrm_contribution`
          LEFT JOIN `civicrm_line_item`
                  ON `civicrm_line_item`.`contribution_id` = `civicrm_contribution`.`id`
                  AND `civicrm_line_item`.$financialTypeClause
          WHERE
              `civicrm_contribution`.`id` IN ($id_string)
          ;";
    }
    else {
      $insert_query =
        "INSERT INTO `donrec_snapshot` (
              `id`,
              `snapshot_id`,
              `profile_id`,
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
              %2 as `profile_id`,
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
    }
    // FIXME: do not include contributions with valued issued don. rec.

    // prepare parameters
    $params = [
      1 => [$new_snapshot_id, 'Integer'],
      2 => [
        $profile->getId(),
        'Int',
      ],
      3 => [$creator_id, 'Integer'],
    ];

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
    }
    else {
      return $return;
    }
  }

  /**
   * deletes the snapshot (permanently on database level!)
   */
  public function delete() {
    return (bool) CRM_Core_DAO::singleValueQuery(
      'DELETE FROM `donrec_snapshot`
       WHERE `snapshot_id` = %1;', [1 => [$this->Id, 'Integer']]);
  }

  /**
   * checks if the snapshot exists, i.e. if there is at least one item
   */
  private function exists() {
    return (bool) CRM_Core_DAO::singleValueQuery(
      'SELECT EXISTS(SELECT 1 FROM `donrec_snapshot`
       WHERE `snapshot_id` = %1);', [1 => [$this->Id, 'Integer']]);
  }

  /**
   * get the snapshot's creator (a contact_id)
   */
  public function getCreator() {
    return (int) CRM_Core_DAO::singleValueQuery(
      'SELECT `created_by` FROM `donrec_snapshot`
       WHERE `snapshot_id` = %1 LIMIT 1;', [1 => [$this->Id, 'Integer']]);
  }

  /**
   * will select a previously unprocessed set of snapshot items
   *
   * @param bool $is_bulk
   * @param bool $is_test
   * @return array<int, list<array<string, mixed>>>|null id => array with values
   */
  public function getNextChunk($is_bulk, $is_test) {
    $chunk_size = CRM_Donrec_Logic_Settings::getChunkSize();
    $snapshot_id = $this->getId();
    $chunk = [];
    if ($is_test) {
      $status_clause = '`status` IS NULL';
    }
    else {
      $status_clause = "(`status` IS NULL OR `status`='TEST')";
    }

    // here, we need a different algorithm for bulk than for single:
    if (empty($is_bulk)) {
      // SINGLE case: just grab $chunk_size items
      $query = "SELECT * FROM `donrec_snapshot` WHERE `snapshot_id` = $snapshot_id
                  AND $status_clause LIMIT $chunk_size;";
      /** @var \CRM_Core_DAO $result */
      $result = CRM_Core_DAO::executeQuery($query);
      while ($result->fetch()) {
        $chunk_line = [];
        foreach (self::$CHUNK_FIELDS as $field) {
          $chunk_line[$field] = $result->$field;
        }
        $chunk[$chunk_line['id']] = $chunk_line;
      }
    }
    else {
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
      /** @var \CRM_Core_DAO $query */
      $query = CRM_Core_DAO::executeQuery($query);

      $last_added_contact_id = NULL;
      $contribution_count = 0;
      while ($query->fetch()) {
        if ($last_added_contact_id != $query->contact_id) {
          // this is a new contact ID
          if ((count($chunk) >= $chunk_size) || ($contribution_count > 5 * $chunk_size)) {
            // we already have $chunk_size contacts, or 5x $chunk_size contributions
            //  => that's enough for this chunk!
            break;
          }

          // ok, we're still under the limit => create a section for the contact
          $chunk[$query->contact_id] = [];
          $last_added_contact_id = $query->contact_id;
        }

        // add contribution
        $contribution = [];
        foreach (self::$CHUNK_FIELDS as $field) {
          $contribution[$field] = $query->$field;
        }
        $chunk[$query->contact_id][] = $contribution;
        $contribution_count += 1;
      }
    }

    // reset the process information for the given chunk
    $this->resetChunk($chunk, $is_bulk);

    if (count($chunk) == 0) {
      return NULL;
    }
    else {
      return $chunk;
    }
  }

  /**
   * reset the process information for the given chunk
   * @param $chunk
   * @param bool $is_bulk
   */
  public function resetChunk($chunk, $is_bulk) {
    if ($chunk == NULL) {
      return;
    }

    if ($is_bulk) {
      // get all second level ids
      $ids = [];
      foreach ($chunk as $ck => $cv) {
        foreach ($cv as $lk => $lv) {
          array_push($ids, $lv['id']);
        }
      }
    }
    else {
      $ids = array_keys($chunk);
    }

    if (empty($ids)) {
      Civi::log()->debug('de.systopia.donrec: invalid chunk detected!');
    }
    else {
      $ids_str = implode(',', $ids);

      // reset process information for all IDs
      $query = "UPDATE `donrec_snapshot` SET `process_data` = NULL WHERE `id` IN ($ids_str);";
      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
   * will mark a chunk as produced by getNextChunk() as being processed
   * @param $chunk
   * @param bool $is_test
   * @param bool $is_bulk
   */
  public function markChunkProcessed($chunk, $is_test, $is_bulk = FALSE) {
    if ($chunk == NULL) {
      return;
    }

    $new_status = $is_test ? 'TEST' : 'DONE';
    if (!$is_bulk) {
      $ids = array_keys($chunk);
    }
    else {
      // get all second level ids
      $ids = [];
      foreach ($chunk as $ck => $cv) {
        foreach ($cv as $lk => $lv) {
          array_push($ids, $lv['id']);
        }
      }
    }

    if (empty($ids)) {
      Civi::log()->debug('de.systopia.donrec: invalid chunk detected!');
    }
    else {
      $ids_str = implode(',', $ids);

      // update process-info-field
      $proc_info['is_bulk'] = $is_bulk;
      foreach ($ids as $id) {
        $this->updateProcessInformation($id, $proc_info);
      }

      // update status-field
      $query = "UPDATE `donrec_snapshot` SET `status`='$new_status' WHERE `id` IN ($ids_str);";
      CRM_Core_DAO::executeQuery($query);
    }
  }

  /**
   * get the snapshot's state distribution
   *
   * @return array
   *   array <state> => <count>
   */
  public function getStates() {
    $states = ['NULL' => 0, 'TEST' => 0, 'DONE' => 0];
    $id = $this->Id;
    $query = "
      SELECT COUNT(`id`) AS count, `status` AS status
      FROM `donrec_snapshot`
      WHERE `snapshot_id` = $id GROUP BY `status`";
    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query);
    while ($result->fetch()) {
      if ($result->status == NULL) {
        $states['NULL'] = $result->count;
      }
      else {
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
      'DELETE FROM `donrec_snapshot`
       WHERE `expires_timestamp` < NOW();');
  }

  /**
   * checks whether there are intersections in snapshots
   * @param int $snapshot_id
   * @return array|FALSE
   */
  public static function hasIntersections($snapshot_id = 0) {
    // TODO: speed up by looking at one particular snapshot ?
    // We do not check snapshots with status DONE: If we delete a receipt but
    // the snapshot still exists, we get an intersection-error on trying to
    // produce the receipt again.
    $query = "
      SELECT original.`snapshot_id`, contact.`display_name`, original.`expires_timestamp`
      FROM `donrec_snapshot` AS original
      INNER JOIN `donrec_snapshot` AS copy ON original.`contribution_id` = copy.`contribution_id`
      AND (original.`status` != 'DONE' OR original.`status` IS NULL)
      AND (copy.`status` != 'DONE' OR copy.`status` IS NULL)
      LEFT JOIN `civicrm_contact` AS contact ON copy.`created_by` = contact.`id`
      WHERE original.`snapshot_id` <> copy.`snapshot_id`
      GROUP BY `snapshot_id`;";

    CRM_Core_DAO::disableFullGroupByMode();
    /** @var \CRM_Core_DAO $results */
    $results = CRM_Core_DAO::executeQuery($query);
    CRM_Core_DAO::reenableFullGroupByMode();
    $intersections = [$snapshot_id];

    while ($results->fetch()) {
      $intersections[] = [(int) $results->snapshot_id, $results->display_name, $results->expires_timestamp];
    }

    if (count($intersections) > 1) {
      return $intersections;
    }
    else {
      return FALSE;
    }
  }

  /**
   * --- HELPER/GETTER/SETTER METHODS ---
   */
  public function getId() {
    return $this->Id;
  }

  public function getIds() {
    $snapshot_id = $this->Id;
    $query = "SELECT `id` FROM `donrec_snapshot` WHERE `snapshot_id` = $snapshot_id;";
    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query);
    $ids = [];
    while ($result->fetch()) {
      $ids[] = $result->id;
    }
    return $ids;
  }

  /**
   * reads and parses the JSON process information field
   * @param int $snapshot_item_id
   * @return array
   */
  public function getProcessInformation($snapshot_item_id) {
    $item_id = (int) $snapshot_item_id;
    if (!$item_id) {
      return [];
    }

    // read value
    $raw_value = CRM_Core_DAO::singleValueQuery(
      "SELECT `process_data` FROM `donrec_snapshot` WHERE `id` = $item_id;");
    if (empty($raw_value)) {
      return [];
    }

    $value = json_decode($raw_value, TRUE);
    if (!is_array($value)) {
      Civi::log()->debug("de.systopia.donrec: warning, cannot decode process_data of ID $item_id!");
      return [];
    }
    else {
      return $value;
    }
  }

  /**
   * sets the JSON process information field
   * @param int $snapshot_item_id
   * @param mixed $value
   * @return bool|null
   */
  public function setProcessInformation($snapshot_item_id, $value) {
    $item_id = (int) $snapshot_item_id;
    if (!$item_id) {
      Civi::log()->debug("de.systopia.donrec: invalid snapshot id detected! ($item_id)");
      return NULL;
    }

    $raw_value = json_encode($value);
    if ($raw_value == FALSE) {
      Civi::log()->debug("de.systopia.donrec: warning, cannot encode process_data for ID $item_id!");

      return NULL;
    }
    else {
      return (bool) CRM_Core_DAO::singleValueQuery(
        'UPDATE `donrec_snapshot`
         SET `process_data` = %1
         WHERE `id` = %2;',
        [1 => [$raw_value, 'String'], 2 => [$item_id, 'Integer']]);
    }
  }

  /**
   * updates the JSON process information field
   * @param int $snapshot_item_id
   * @param array $array
   */
  public function updateProcessInformation($snapshot_item_id, $array) {
    $infos = $this->getProcessInformation($snapshot_item_id);
    $merged_infos = array_merge($infos, $array);
    $this->setProcessInformation($snapshot_item_id, $merged_infos);
  }

  /**
   * Returns a line of this snapshot
   * @param int $line_id
   * @return array<string, scalar|null> or empty array
   */
  public function getLine($line_id) {
    $snapshot_id = $this->Id;
    $query = "SELECT * FROM `donrec_snapshot` WHERE `snapshot_id` = $snapshot_id AND id = %1 LIMIT 1;";
    $params = [1 => [$line_id, 'Integer']];
    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query, $params);
    $result->fetch();
    $line = [];
    foreach (self::$LINE_FIELDS as $field) {
      $line[$field] = $result->$field;
    }
    return $line;
  }

  /**
   * Checks if a snapshot is marked as bulk or single
   * @param int $id
   * @return string|null bulk|single or null
   */
  // TODO: refactor this. Process-infos are already accessed in getExporters.

  /**
   * Use a common method to not fetch process-infos twice.
   */
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
      return NULL;
    }
    // decode process-data
    $info = json_decode($raw_value, TRUE);

    // if it could not be decoded: abort
    if (!is_array($info)) {
      Civi::log()->debug('de.systopia.donrec: warning, cannot decode process_data!');
      return NULL;
    }

    // is_bulk was not set: abort
    if (!array_key_exists('is_bulk', $info)) {
      return NULL;
    }
    elseif ($info['is_bulk']) {
      return 'bulk';
    }
    else {
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
    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query);

    // merge process-data of all items into array
    $merged_process_data = [];
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
      $exporters = ['Dummy'];
    }
    return $exporters;
  }

  /**
   * Returns an array with statistic values of the snapshot
   * @param int $id
   * @return array
   */
  public static function getStatistic($id) {
    $query1 = "SELECT
      COUNT(*) AS contribution_count,
      SUM(total_amount) AS total_amount,
      created_timestamp AS creation_date,
      date_from AS date_from,
      date_to AS date_to,
      currency AS currency
      FROM donrec_snapshot
      WHERE snapshot_id = $id";

    $query2 = "SELECT COUNT(*)
      FROM (
        SELECT contact_id
        FROM donrec_snapshot
        WHERE snapshot_id = $id
        GROUP BY contact_id
      ) A";
    CRM_Core_DAO::disableFullGroupByMode();
    /** @var \CRM_Core_DAO $result1 */
    $result1 = CRM_Core_DAO::executeQuery($query1);
    CRM_Core_DAO::reenableFullGroupByMode();
    $result1->fetch();

    // get status of the snapshot
    // TODO: we need to create a snapshot-object because getStates is not a
    // static function. Therefore it would make sense to rewrite getStatistic
    // as a object-method as well.
    $snapshot = self::get($id);
    $states = $snapshot->getStates();
    // if we have TEST- and DONE-states we have a problem
    if ($states['TEST'] && $states['DONE']) {
      Civi::log()->debug("de.systopia.donrec - snapshot with id $id has entries with both TEST and DONE states!");
      // TODO : raise an error
      $status = NULL;
    }
    elseif ($states['TEST']) {
      $status = 'TEST';
    }
    elseif ($states['DONE']) {
      $status = 'DONE';
    }
    else {
      $status = NULL;
    }

    $statistic = [
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
      'currency' => $result1->currency,
    ];
    return $statistic;
  }

  /**
   * Returns an array with ids of already existing snapshots of a specific
   * user.
   * @param int $creator_id
   * @return array
   */
  public static function getUserSnapshots($creator_id) {
    $remaining_snapshots = [];

    $query = "
      SELECT snapshot_id
      FROM donrec_snapshot
      WHERE (status IS NULL OR status != 'DONE')
      AND created_by = $creator_id
      GROUP BY snapshot_id";

    /** @var \CRM_Core_DAO $result */
    $result = CRM_Core_DAO::executeQuery($query);
    while ($result->fetch()) {
      array_push($remaining_snapshots, $result->snapshot_id);
    }
    return $remaining_snapshots;
  }

  /**
   * Deletes all not processed snapshots of a given user.
   * @param int $creator_id
   * @return \CRM_Core_DAO|object return-value from CRM_Core_DAO::executeQuery()
   */
  public static function deleteUserSnapshots($creator_id) {
    $remaining_snapshots = [];

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
   * @param int $contribution_id
   * @param bool $return_id
   * @return bool|int
   */
  public static function isInOpenSnapshot($contribution_id, $return_id = FALSE) {
    // do a cleanup here (ticket #1616)
    self::cleanup();

    // TODO: what if status is DONE, but the snapshot is not finished yet?
    $query = "
      SELECT `id`
      FROM `donrec_snapshot`
      WHERE contribution_id = $contribution_id
      AND (status IS NULL OR status != 'DONE')
    ";
    $result = CRM_Core_DAO::singleValueQuery($query);
    if ($return_id && NULL !== $result) {
      return (int) $result;
    }
    else {
      return NULL !== $result;
    }
  }

  /**
   * create a set of CRM_Donrec_Logic_SnapshotReceipt objects with a given chunk
   *
   * @param array $chunk
   * @param bool $is_bulk
   * @param bool $is_test
   * @return array
   *   array of CRM_Donrec_Logic_SnapshotReceipts
   */

  /**
   * FIXME: This function seems to be superfluous. Now where used anymore
   */
  public function getSnapshotReceipts($chunk, $is_bulk, $is_test) {
    $temp_receipts = [];
    if ($is_bulk) {
      // then create the temporary receipts
      foreach ($chunk as $contact_id => $snapshot_lines) {
        $temp_receipts[] = new CRM_Donrec_Logic_SnapshotReceipt($this, $snapshot_lines, $is_test);
      }
    }
    else {
      // create individual receipts
      foreach ($chunk as $snapshot_line) {
        $temp_receipts[] = new CRM_Donrec_Logic_SnapshotReceipt($this, [$snapshot_line], $is_test);
      }
    }
    return $temp_receipts;
  }

  /**
   * Get a CRM_Donrec_Logic_SnapshotReceipt object
   * @param list<int> $snapshot_line_ids
   * @param bool $is_test
   * @return CRM_Donrec_Logic_SnapshotReceipt
   */
  public function getSnapshotReceipt($snapshot_line_ids, $is_test) {
    $lines = [];
    foreach ($snapshot_line_ids as $id) {
      $lines[] = $this->getLine($id);
    }
    return new CRM_Donrec_Logic_SnapshotReceipt($this, $lines, $is_test);
  }

  /**
   * Get the profile connected to this snapshot
   *
   * @return \CRM_Donrec_Logic_Profile
   */
  public function getProfile() {
    if ($this->_profile === NULL) {
      $profile_id = (int) CRM_Core_DAO::singleValueQuery(
        'SELECT profile_id FROM donrec_snapshot WHERE snapshot_id = %1 LIMIT 1;',
        [1 => [$this->Id, 'Integer']]);
      $this->_profile = CRM_Donrec_Logic_Profile::getProfile($profile_id);
    }
    return $this->_profile;
  }

}
