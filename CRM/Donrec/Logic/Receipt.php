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
 * This class represents a single donation receipt
 */
class CRM_Donrec_Logic_Receipt extends CRM_Donrec_Logic_ReceiptTokens {
  /**
  * Custom field array to map attribute names to database colums
  * i.e. self::$_custom_field['status'] == 'status_38'
  */
  protected static $_custom_fields;
  protected static $_custom_group_id;

  /**
  * Receipt id
  */
  protected $Id;

  /**
   * constructor
   *
   * @param int $receipt_id
   */
  public function __construct($receipt_id) {
    self::getCustomFields();
    $this->Id = $receipt_id;
  }

  /**
   * get an existing receipt
   * @param int $receipt_id
   * @return \CRM_Donrec_Logic_Receipt | NULL
   */
  public static function get($receipt_id) {
    self::getCustomFields();
    $receipt = new self($receipt_id);
    if ($receipt->exists()) {
      return $receipt;
    } else {
      return NULL;
    }
  }


  /**
  * Creates a receipt without receipt-items using the generic tokens
  * @param array $tokens             tokens coming from SnapshotReceipt->getAllTokens
  * @return \CRM_Core_DAO | bool
   *   Receipt object if successfull, FALSE otherwise.
  */
  public static function _createReceiptFromTokens($tokens) {
    // initialize custom field map
    self::getCustomFields();
    $fields = self::$_custom_fields;
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');

    // build SET-SQL
    $sql_set = "`entity_id`=$tokens[contact_id]";
    foreach ($fields as $key => $field) {
      $value = null;
      if ($key == 'profile_id') {
        $value = CRM_Donrec_Logic_Profile::getProfile($tokens['profile_id'])->getId();
      }
      elseif (0 === strpos($key, 'shipping')) {
        $token_key = substr($key, strlen('shipping_'));
        $value = $tokens['addressee'][$token_key];
      } elseif ($tokens[$key]) {
        $value = $tokens[$key];
      } elseif ($tokens['contributor'][$key]) {
        $value = $tokens['contributor'][$key];
      }
      if (!is_null($value)) {
        $value = CRM_Utils_DonrecHelper::escapeString($value);
        $sql_set .= ", `$field`='$value'";
      }
    }

    // build query
    $query = "INSERT INTO `$table` SET $sql_set";

    // Lock profile (mark as used for issueing receipts).
    CRM_Donrec_Logic_Profile::getProfile($tokens['profile_id'])->lock();

    // run the query
    return CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Creates a new receipt and belonging receipt-items
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt   a snapshotReceipt object
   * @param array $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return self | bool
   *   TRUE if successful, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  // TODO: createFromSnapshotReceipt and createFromSnapshot could be refactored to avoid redundant code
  public static function createFromSnapshotReceipt($snapshot_receipt, &$parameters) {
    $tokens = $snapshot_receipt->getAllTokens();
    $parameters['profile_id'] = $snapshot_receipt->getProfile()->getId();

    // error if no tokens found
    if (empty($tokens)) {
      $parameters['is_error'] = "snapshot-line-ids does not exist.";
      return FALSE;
    }

    // update tokens from parameters
    $tokens = array_merge($tokens, $parameters);

    // create receipt
    $result = self::_createReceiptFromTokens($tokens);

    // create receipt-items
    $lastId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    foreach ($tokens['lines'] as $lid => $line_tokens) {
      $params = array_merge($tokens, $line_tokens);
      $params['issued_in'] = $lastId;
      $params['receipt_id'] = $snapshot_receipt->getReceiptID();
      CRM_Donrec_Logic_ReceiptItem::create($params);
    }

    return new self($lastId);
  }

  /**
   * Creates a new receipt and belonging receipt-items
   *
   * @param \CRM_Donrec_Logic_Snapshot $snapshot           a snapshot object
   * @param arra $snapshot_line_ids  an array with the IDs of the snapshot lines to be used for creation
   * @param array $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return self | bool
   *   TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public static function createFromSnapshot($snapshot, $snapshot_line_ids, &$parameters) {
    // get all tokens form SnapshotReceipt
    $snapshot_receipt = $snapshot->getSnapshotReceipt($snapshot_line_ids, FALSE);
    $tokens = $snapshot_receipt->getAllTokens();

    // error if no tokens found
    if (empty($tokens)) {
      $parameters['is_error'] = "snapshot-line-ids does not exist.";
      return FALSE;
    }

    // update tokens from parameters
    $tokens = array_merge($tokens, $parameters);

    // create receipt
    $result = self::_createReceiptFromTokens($tokens);

    // create receipt-items
    $lastId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    foreach ($tokens['lines'] as $lid => $line_tokens) {
      $params = array_merge($tokens, $line_tokens);
      $params['issued_in'] = $lastId;
      $params['receipt_id'] = $snapshot->getReceiptID();
      CRM_Donrec_Logic_ReceiptItem::create($params);
    }

    return new self($lastId);
  }

  /**
   * Creates a copy of this receipt. The receipt status will be 'COPY'
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function createCopy() {
    $receipt_id = $this->Id;
    $receipt_fields = self::$_custom_fields;
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $uid = CRM_Donrec_Logic_Settings::getLoggedInContactID();

    $exclude = array('status', 'issued_on', 'issued_by', 'original_file');
    $field_query = '`entity_id`';
    foreach($receipt_fields as $key => $field) {
      if (!in_array($key, $exclude)) {
        $field_query .= ", `$field`";
      }
    }
    $query = "
      INSERT INTO `$receipt_table` (
        `$receipt_fields[status]`,
        `$receipt_fields[issued_on]`,
        `$receipt_fields[issued_by]`,
        $field_query
      )
      SELECT
        'COPY' AS `$receipt_fields[status]`,
        NOW() AS `$receipt_fields[issued_on]`,
        $uid AS `$receipt_fields[issued_by]`,
        $field_query
      FROM `$receipt_table`
      WHERE `id` = $receipt_id
        AND `$receipt_fields[status]` = 'ORIGINAL'
    ";

    $result = CRM_Core_DAO::executeQuery($query);
    $lastId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID();');
    CRM_Donrec_Logic_ReceiptItem::createCopyAll($this->Id, $lastId);
    return TRUE;
  }

  /**
   * Delete this receipt.
   *
   * @param array $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return bool
   *   TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function delete(&$parameters) {
    self::getCustomFields();
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');

    // delete pdf file if exists


    $status = empty($parameters['status']) ? 'ORIGINAL' : $parameters['status'];
    $query = "DELETE FROM `$receipt_table` WHERE `id` = %d";
    $query = sprintf($query, $this->Id);
    $result = CRM_Core_DAO::executeQuery($query, $status);

    CRM_Donrec_Logic_ReceiptItem::deleteAll($this->Id);

    return TRUE;
  }

  /**
   * Mark this receipt and its items as withdrawn
   *
   * @param string $status
   *
   * @return bool
   *   TRUE if successfull, FALSE otherwise. In that case, the
   *   $parameters['error'] contains an error message.
   */
  public function setStatus($status) {
    $receipt_id = $this->Id;
    $receipt_fields = self::$_custom_fields;
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $query = "
      UPDATE `$receipt_table`
      SET `$receipt_fields[status]` = '$status'
      WHERE `id` = $receipt_id
    ";
    $result = CRM_Core_DAO::executeQuery($query);
    CRM_Donrec_Logic_ReceiptItem::setStatusAll($receipt_id, $status);
    // TODO: error-handling
    return TRUE;
  }

  /**
   * Get all copies of this receipt.
   * @return array
   *  array of receipt-objects
   */
  public function getCopies() {
    $receipt_id = $this->Id;
    $receipt_fields = self::$_custom_fields;
    CRM_Donrec_Logic_ReceiptItem::getCustomFields();
    $item_fields = CRM_Donrec_Logic_ReceiptItem::$_custom_fields;
    $receipt_table_name = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $item_table_name = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $query = "
      SELECT c.id
      FROM `$receipt_table_name` o
      RIGHT JOIN `$receipt_table_name` c
        ON o.`id` = $receipt_id
        AND o.`$receipt_fields[status]` = 'ORIGINAL'
        AND c.`$receipt_fields[status]` = 'COPY'
        AND o.entity_id = c.entity_id
      RIGHT JOIN `$item_table_name` io
        ON io.`$item_fields[issued_in]` = o.id
      RIGHT JOIN `$item_table_name` ic
        ON ic.`$item_fields[issued_in]` = c.id
      WHERE io.entity_id = ic.entity_id;
    ";
    $result = CRM_Core_DAO::executeQuery($query);
    $copies = array();
    while ($result->fetch()) {
      array_push($copies, self::get($result->id));
    }
    return $copies;
  }

  /**
   * Get all properties of this receipt, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * @return array of properties
   */
  public function getAllProperties() {
    // TODO: Remove stub
    return $this->getAllTokens();
  }

  /**
   * Get all the properties of this receipt needed for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return array
   *   an array of all properties needed for display
   */
  public function getDisplayProperties() {
    // TODO: Remove stub
    return $this->getDisplayTokens();
  }

  /**
   * Find all receipts for the given contact ID
   *
   * @param int $contact_id    a contact ID
   * @param array $parameters    TODO: to be definied. Maybe for only to restrict search (like 'only copies')
   *
   * @return array
   *   array of CRM_Donrec_Logic_Receipt instances
   */
  public static function getReceiptsForContact($contact_id, &$parameters) {
    self::getCustomFields();
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $query = "SELECT `id` FROM `$receipt_table` WHERE `entity_id` = %d ORDER BY `%s` DESC;";
    $query = sprintf($query, $contact_id, self::$_custom_fields['issued_on']);
    $results = CRM_Core_DAO::executeQuery($query);
    $receipts = array();

    while($results->fetch()) {
      $receipts[] = new self($results->id);
    }

    return $receipts;
  }

  /**
   * Get the IDs of the receipt that matches all the criteria below
   *
   * @param $contribution_id              integer  a contribution part of the receipt
   * @param $issue_timestamp              string   timestamp (to be parsed with strtotime)
   * @param $issue_timestamp_precision    integer  +/- deviation from timestamp in seconds
   * @param $status                       string   receipt status
   * @return array receipt IDs
   */
  public static function getReceiptIDsByContributionID($contribution_id, $issue_timestamp = NULL, $issue_timestamp_precision = 60, $status = NULL) {
    $receipt_ids = array();
    $receipt_table   = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $item_table      = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $receipt_fields  = self::getCustomFields();
    $item_fields     = CRM_Donrec_Logic_ReceiptItem::getCustomFields();

    // generate timestamp clause
    if ($issue_timestamp) {
      $timestamp = strtotime($issue_timestamp);
      $min_time = date("YmdHis", strtotime("-{$issue_timestamp_precision} seconds", $timestamp));
      $max_time = date("YmdHis", strtotime("+{$issue_timestamp_precision} seconds", $timestamp));
      $timestamp_clause = "(receipt.{$receipt_fields['issued_on']} >= {$min_time} AND receipt.{$receipt_fields['issued_on']} <= {$max_time})";
    } else {
      $timestamp_clause = "TRUE";
    }

    // generate status clause
    if ($status) {
      $status_clause = "receipt.{$receipt_fields['status']} = '{$status}'";
    } else {
      $status_clause = 'TRUE';
    }

    // compile the query
    $receipt_query = "
    SELECT receipt.id AS receipt_id
    FROM {$receipt_table}   receipt
    LEFT JOIN {$item_table} item    ON item.{$item_fields['issued_in']} = receipt.id
    WHERE item.entity_id = {$contribution_id}
      AND {$timestamp_clause}
      AND {$status_clause}
    GROUP BY receipt.id;";

    // run the query
    $query = CRM_Core_DAO::executeQuery($receipt_query);
    while ($query->fetch()) {
      $receipt_ids[] = $query->receipt_id;
    }
    return $receipt_ids;
  }

   /**
   * Get number of all receipts for the given contact ID
   *
   * @param $contact_id    a contact ID
   *
   * @return int
   */
  public static function getReceiptCountForContact($contact_id) {
    self::getCustomFields();
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $query = "SELECT COUNT(`id`) FROM `$receipt_table` WHERE `entity_id` = %d";
    $query = sprintf($query, $contact_id);
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Checks if there is a VALID donation receipt for the given contribution
   *
   * This method should be HIGHLY optimized
   *
   * @param int $contribution_id
   * @return bool
   *   TRUE if there is a VALID donation receipt, FALSE otherwise
   */
  public static function isContributionLocked($contribution_id) {
    if (empty($contribution_id)) {
      return TRUE;
    } else {
      return CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($contribution_id);
    }
  }

  /**
  * Returns the id of this receipt
  * @return int
  */
  public function getId() {
    return $this->Id;
  }

  /**
   * checks if the receipt exists, i.e. if there is at least one item
   */
  private function exists() {
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    return (bool)CRM_Core_DAO::singleValueQuery(
      "SELECT EXISTS(SELECT 1 FROM `$receipt_table`
       WHERE `id` = %1);", array(1 => array($this->Id, 'Integer')));
  }

  /**
  * Checks whether this receipt is an original
  * @return bool TRUE if the receipt is an original otherwise FALSE
  */
  public function isOriginal() {
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $status = self::$_custom_fields['status'];
    return (bool)CRM_Core_DAO::singleValueQuery(
      "SELECT EXISTS(SELECT 1 FROM `$receipt_table`
       WHERE `id` = %1 AND `$status` = 'ORIGINAL');", array(1 => array($this->Id, 'Integer')));
  }

  /**
  * Updates the class attribute to contain all custom fields of the
  * donation receipt database table.
  */
  public static function getCustomFields() {
    if (self::$_custom_fields === NULL) {
      // get the ids of all relevant custom fields
      $params = array(
        'version'  => 3,
        'name'     => 'zwb_donation_receipt',
      );
      $custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
      if (isset($custom_group['is_error'])) {
        CRM_Core_Error::debug_log_message(sprintf('de.systopia.donrec: getCustomFields: error: %s', $custom_group['error_message']));
        return NULL;
      }

      self::$_custom_group_id = $custom_group['id'];

      $params = array(
        'version'         => 3,
        'custom_group_id' => $custom_group['id'],
        'option.limit'    => 999
      );
      $custom_fields = civicrm_api('CustomField', 'get', $params);
      if ($custom_fields['is_error'] != 0) {
        CRM_Core_Error::debug_log_message(sprintf('de.systopia.donrec: getCustomFields: error: %s', $custom_fields['error_message']));
        return NULL;
      }

      self::$_custom_fields = array();
      foreach ($custom_fields['values'] as $field) {
        self::$_custom_fields[$field['name']] = $field['column_name'];
      }
    }
    return self::$_custom_fields;
  }

  /**
   * Get all properties of this receipt token source, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * @return array of properties
   */
  public function getAllTokens() {
    $values = array();

    CRM_Donrec_Logic_ReceiptItem::getCustomFields();
    $expected_fields = CRM_Donrec_Logic_ReceiptTokens::$STORED_TOKENS;
    $receipt_id = $this->Id;
    $receipt_fields = self::$_custom_fields;
    $item_fields = CRM_Donrec_Logic_ReceiptItem::$_custom_fields;
    $receipt_table_name = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $item_table_name = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');

    // get all the receipt data
    $query_receipt = "SELECT
        receipt.`id`                                       AS `id`,
        receipt.`entity_id`                                AS `contributor__id`,
        receipt.`$receipt_fields[receipt_id]`              AS `receipt_id`,
        receipt.`$receipt_fields[profile_id]`              AS `profile_id`,
        receipt.`$receipt_fields[type]`                    AS `type`,
        receipt.`$receipt_fields[exporters]`               AS `exporters`,
        receipt.`$receipt_fields[status]`                  AS `status`,
        receipt.`$receipt_fields[issued_on]`               AS `issued_on`,
        receipt.`$receipt_fields[issued_by]`               AS `issued_by`,
        receipt.`$receipt_fields[original_file]`           AS `original_file`,
        receipt.`$receipt_fields[date_from]`               AS `date_from`,
        receipt.`$receipt_fields[date_to]`                 AS `date_to`,

        receipt.`$receipt_fields[display_name]`            AS `contributor__display_name`,
        receipt.`$receipt_fields[street_address]`          AS `contributor__street_address`,
        receipt.`$receipt_fields[postal_greeting_display]` AS `contributor__postal_greeting_display`,
        receipt.`$receipt_fields[email_greeting_display]`  AS `contributor__email_greeting_display`,
        receipt.`$receipt_fields[addressee_display]`       AS `contributor__addressee_display`,
        receipt.`$receipt_fields[supplemental_address_1]`  AS `contributor__supplemental_address_1`,
        receipt.`$receipt_fields[supplemental_address_2]`  AS `contributor__supplemental_address_2`,
        receipt.`$receipt_fields[postal_code]`             AS `contributor__postal_code`,
        receipt.`$receipt_fields[city]`                    AS `contributor__city`,
        receipt.`$receipt_fields[country]`                 AS `contributor__country`,

        receipt.`$receipt_fields[shipping_street_address]`          AS `addressee__street_address`,
        receipt.`$receipt_fields[shipping_supplemental_address_1]`  AS `addressee__supplemental_address_1`,
        receipt.`$receipt_fields[shipping_supplemental_address_2]`  AS `addressee__supplemental_address_2`,
        receipt.`$receipt_fields[shipping_postal_code]`             AS `addressee__postal_code`,
        receipt.`$receipt_fields[shipping_city]`                    AS `addressee__city`,
        receipt.`$receipt_fields[shipping_country]`                 AS `addressee__country`,

        SUM(item.`$item_fields[total_amount]`)             AS `total_amount`,
        SUM(item.`$item_fields[non_deductible_amount]`)    AS `non_deductible_amount`,
        item.`$item_fields[currency]`                      AS `currency`

      FROM `$receipt_table_name`                           AS receipt

      RIGHT JOIN `$item_table_name`                        AS item
        ON   item.`$item_fields[issued_in]` = receipt.`id`
        AND  item.`$item_fields[status]`    = receipt.`$receipt_fields[status]`

      WHERE receipt.`id` = $receipt_id";

    $result = CRM_Core_DAO::executeQuery($query_receipt);
    if ($result->fetch()) {
      foreach ($expected_fields as $key => $value) {
        // copy all expected values, if they exist
        if (!is_array($key) && isset($result->$key)) {
          $values[$key] = $result->$key;
        }
      }

      // also, copy the contributor data
      foreach ($expected_fields['contributor'] as $key => $value) {
        $qkey = 'contributor__' . $key;
        if (isset($result->$qkey)) {
          $values['contributor'][$key] = $result->$qkey;
        }
      }

      // and the addresse data
      foreach ($expected_fields['addressee'] as $key => $value) {
        $qkey = 'addressee__' . $key;
        if (isset($result->$qkey)) {
          $values['addressee'][$key] = $result->$qkey;
        }
      }
    } else {
      CRM_Core_Error::debug_log_message("de.systopia.donrec - couldn't load receipt data.");
      return $values;
    }

    // get receipt-item-infos
    $query_item = "
      SELECT
        item.`id`                                        AS `id`,
        item.`entity_id`                                 AS `contribution_id`,
        item.`$item_fields[receive_date]`                AS `receive_date`,
        item.`$item_fields[total_amount]`                AS `total_amount`,
        item.`$item_fields[issued_on]`                   AS `currency`,
        item.`$item_fields[non_deductible_amount]`       AS `non_deductible_amount`,
        item.`$item_fields[financial_type_id]`           AS `financial_type_id`,
        type.`name`                                      AS `type`

      FROM `$item_table_name`                            AS item
      INNER JOIN `civicrm_contribution`                  AS contrib
        ON   item.`entity_id` = contrib.`id`
      INNER JOIN `civicrm_financial_type`                AS type
        ON   type.`id` = contrib.`financial_type_id`

      WHERE item.`$item_fields[issued_in]` = $receipt_id";

    $result = CRM_Core_DAO::executeQuery($query_item);
    while ($result->fetch()) {
      foreach ($expected_fields['lines'] as $key => $value) {
        if (isset($result->$key)) {
          $values['lines'][$result->id][$key] = $result->$key;
        }
      }
    }

    // add dynamically created tokens
    CRM_Donrec_Logic_ReceiptTokens::addDynamicTokens($values, self::getProfile());

    return $values;
  }

  /**
   * Get all properties of this receipt token sourceneeded for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return array
   *   array of all properties needed for display
   */
  public function getDisplayTokens() {
    // TODO: optimize
    return $this->getAllTokens();
  }

  /**
  * Delete original-file if exists
  * @return TRUE for success, FALSE for failure
  */
  public function deleteOriginalFile() {
    $file_id = self::getOriginalFileId();
    if (!$file_id) {
      return FALSE;
    }
    $receipt_fields = self::$_custom_fields;
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $receipt_id = $this->Id;
    $query = "
      UPDATE `$receipt_table`
      SET `$receipt_fields[original_file]` = NULL
      WHERE id = $receipt_id
    ";
    $result = CRM_Core_DAO::executeQuery($query);
    $success = CRM_Donrec_Logic_File::deleteFile($file_id);
    return $success;
  }

  /**
   * Get url to pdf exists.
   * @return string|null file-name or NULL
   */
  public function getOriginalFileId() {
    $receipt_fields = self::$_custom_fields;
    $receipt_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $receipt_id = $this->Id;
    $query = "
      SELECT `$receipt_fields[original_file]`
      FROM `$receipt_table`
      WHERE `id` = $receipt_id
    ";
    $file_id = CRM_Core_DAO::singleValueQuery($query);
    return $file_id;
  }


  /**
   * get the profile object that was used to create this receipt
   */
  public function getProfile() {
    CRM_Donrec_Logic_ReceiptItem::getCustomFields();
    $receipt_table_name = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $profile_column_name = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt')['profile_id'];
    $profile_id = CRM_Core_DAO::singleValueQuery("SELECT `$profile_column_name` FROM `$receipt_table_name` WHERE `id` = %1", array(1=>array($this->Id, 'Integer')));
    return CRM_Donrec_Logic_Profile::getProfile($profile_id);
  }
}
