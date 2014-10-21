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
 * This class represents a single donation receipt
 */
class CRM_Donrec_Logic_Receipt {
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
  */
  public function __construct($receipt_id) {
    self::getCustomFields();
    $this->Id = $receipt_id;
  }

  /**
  * get an existing receipt
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
  * Creates a new receipt with the given snapshot line
  *
  * @param $snapshot           a snapshot object
  * @param $snapshot_line_id   the ID of the snapshot line to be used for creation
  * @param $parameters         an assoc. array of creation parameters TODO: to be defined
  *
  * @return Receipt object if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
  */
  public static function createSingleFromSnapshot($snapshot, $snapshot_line_id, &$parameters) {
    // initialize custom field map
    self::getCustomFields();

    $line = $snapshot->getLine($snapshot_line_id);
    if (empty($line)) {
      $parameters['is_error'] = "snapshot line #$snapshot_line_id does not exist";
      return FALSE;
    }

    //fields => values
    $key_value = array(
      'entity_id' => $line[contact_id],
      self::$_custom_fields['status'] => "'ORIGINAL'",
      self::$_custom_fields['type'] => "'SINGLE'",
      self::$_custom_fields['issued_on'] => "'$line[created_timestamp]'",
      self::$_custom_fields['issued_by'] => $line[created_by],
      self::$_custom_fields['original_file'] => empty($parameters['file_id']) ? -1 : $parameters['file_id'],
      self::$_custom_fields['street_address'] => empty($line['street_address']) ? 'NULL': "'$line[street_address]'",
      self::$_custom_fields['supplemental_address_1'] => empty($line['supplemental_address_1']) ? 'NULL': "'$line[supplemental_address_1]'",
      self::$_custom_fields['supplemental_address_2'] => empty($line['supplemental_address_2']) ? 'NULL': "'$line[supplemental_address_1]'",
      self::$_custom_fields['supplemental_address_3'] => empty($line['supplemental_address_3']) ? 'NULL': "'$line[supplemental_address_1]'",
      self::$_custom_fields['postal_code'] => empty($line['postal_code']) ? 'NULL' : $line['postal_code'],
      self::$_custom_fields['city'] => empty($line['city']) ? 'NULL': "'$line[city]'",
      self::$_custom_fields['country'] => empty($line['country']) ? 'NULL': "'$line[country]'"
    );

    // build the query
    $custom_group_id = self::$_custom_group_id;
    $sql_set = '';
    foreach ($key_value as $key => $value) {
      $sql_set .= "`$key`=$value, ";
    }
    $sql_set = rtrim($sql_set, ", ");
    $query = "
      INSERT INTO `civicrm_value_donation_receipt_$custom_group_id`
      SET $sql_set";

    $result = CRM_Core_DAO::executeQuery($query);
    $lastId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID();');

    // create the donation_receipt_item
    $item_params = array();
    $item_params['contribution_id'] = $line['contribution_id'];
    $item_params['status'] = 'ORIGINAL';
    $item_params['type']  = 'SINGLE';
    $item_params['issued_in'] = $lastId;
    $item_params['issued_by'] = $line['created_by'];
    $item_params['total_amount'] = $line['total_amount'];
    $item_params['non_deductible_amount'] = $line['non_deductible_amount'];
    $item_params['currency'] = $line['currency'];
    $item_params['issued_on'] = $line['created_timestamp'];
    $item_params['receive_date'] = $line['receive_date'];

    // calculate sha1 checksum
    $contrib_string = "";
    foreach ($item_params as $key => $value) {
      $contrib_string .= $value;
    }
    $item_params['contribution_hash'] = sha1($contrib_string);

    CRM_Donrec_Logic_ReceiptItem::create($item_params);

    return new self($lastId);
  }

  /**
   * Creates a new bulk receipt with the given snapshot lines
   *
   * @param $snapshot           a snapshot object
   * @param $snapshot_line_ids  an array with the IDs of the snapshot lines to be used for creation
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public static function createBulkFromSnapshot($snapshot, $snapshot_line_ids, &$parameters) {
    // initialize custom field map
    self::getCustomFields();

    $lines = array();
    foreach ($snapshot_line_ids as $lid) {
      $line = $snapshot->getLine($lid);
      if (!empty($line)) {
        $lines[] = $line;
      }
    }

    if (empty($lines)) {
      $parameters['is_error'] = "snapshot lines do not exist";
      return FALSE;
    }

    $line = $lines[0];
    if (empty($line)) {
      $parameters['is_error'] = "snapshot line #$snapshot_line_id does not exist";
      return FALSE;
    }

    $query = sprintf("INSERT INTO `civicrm_value_donation_receipt_%d` (`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`,  `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`)
                      VALUES (NULL, %%d, %%s, %%s, %s, %%d, %%d, %%s, %%s, %%s, %%s, %%d, %%s, %%s);",
                self::$_custom_group_id,
                self::$_custom_fields['status'],
                self::$_custom_fields['type'],
                self::$_custom_fields['issued_on'],
                self::$_custom_fields['issued_by'],
                self::$_custom_fields['original_file'],
                self::$_custom_fields['street_address'],
                self::$_custom_fields['supplemental_address_1'],
                self::$_custom_fields['supplemental_address_2'],
                self::$_custom_fields['supplemental_address_3'],
                self::$_custom_fields['postal_code'],
                self::$_custom_fields['city'],
                self::$_custom_fields['country'],
                "'" . $line['created_timestamp'] . "'"
              );

    $query = sprintf($query,
                    $line['contact_id'],
                    "'ORIGINAL'",
                    "'BULK'",
                    $line['created_by'],
                    empty($parameters['file_id']) ? -1 : $parameters['file_id'],
                    empty($line['street_address']) ? "NULL": "'" . $line['street_address'] . "'",
                    empty($line['supplemental_address_1']) ? "NULL" : "'" . $line['supplemental_address_1'] . "'" ,
                    empty($line['supplemental_address_2']) ? "NULL" : "'" . $line['supplemental_address_2'] . "'" ,
                    empty($line['supplemental_address_3']) ? "NULL" : "'" . $line['supplemental_address_3'] . "'" ,
                    empty($line['postal_code']) ? "NULL" : "'" . $line['postal_code'] . "'",
                    empty($line['city']) ? "NULL" : "'" . $line['city'] . "'" ,
                    empty($line['country']) ? "NULL" : "'" . $line['country'] . "'"
                    );

    $result = CRM_Core_DAO::executeQuery($query);
    $lastId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID();');

    for ($i=0; $i < count($lines); $i++) {
      // create the donation_receipt_item
      $item_params = array();
      $item_params['contribution_id'] = $lines[$i]['contribution_id'];
      $item_params['status'] = 'ORIGINAL';
      $item_params['type']  = 'BULK';
      $item_params['issued_in'] = $lastId;
      $item_params['issued_by'] = $lines[$i]['created_by'];
      $item_params['total_amount'] = $lines[$i]['total_amount'];
      $item_params['non_deductible_amount'] = $lines[$i]['non_deductible_amount'];
      $item_params['currency'] = $lines[$i]['currency'];
      $item_params['issued_on'] = $lines[$i]['created_timestamp'];
      $item_params['receive_date'] = $lines[$i]['receive_date'];

      // calculate sha1 checksum
      $contrib_string = "";
      foreach ($item_params as $key => $value) {
        $contrib_string .= $value;
      }
      $item_params['contribution_hash'] = sha1($contrib_string);

      CRM_Donrec_Logic_ReceiptItem::create($item_params);
    }

    return new self($lastId);
  }

  /**
   * Creates a copy of this receipt. The receipt status will be 'COPY'
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function createCopy(&$parameters) {
    $query = "INSERT INTO `civicrm_value_donation_receipt_%d`
              (`id`,
               `entity_id`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`,
               `%s`)
              SELECT
              NULL as `id`,
              `entity_id`,
              'COPY' as `%s`,
              `%s`,
              NOW() as `%s`,
              `%s`,
              `%s`,
              `%s`,
              `%s`,
              `%s`,
              `%s`,
              `%s`,
              `%s`,
              `%s`
              FROM `civicrm_value_donation_receipt_%d`
              WHERE `id` = %d AND `%s` = 'ORIGINAL';";
    $query = sprintf($query,
                    self::$_custom_group_id,
                    self::$_custom_fields['status'],
                    self::$_custom_fields['type'],
                    self::$_custom_fields['issued_on'],
                    self::$_custom_fields['issued_by'],
                    self::$_custom_fields['original_file'],
                    self::$_custom_fields['street_address'],
                    self::$_custom_fields['supplemental_address_1'],
                    self::$_custom_fields['supplemental_address_2'],
                    self::$_custom_fields['supplemental_address_3'],
                    self::$_custom_fields['postal_code'],
                    self::$_custom_fields['city'],
                    self::$_custom_fields['country'],
                    self::$_custom_fields['status'],
                    self::$_custom_fields['type'],
                    self::$_custom_fields['issued_on'],
                    self::$_custom_fields['issued_by'],
                    self::$_custom_fields['original_file'],
                    self::$_custom_fields['street_address'],
                    self::$_custom_fields['supplemental_address_1'],
                    self::$_custom_fields['supplemental_address_2'],
                    self::$_custom_fields['supplemental_address_3'],
                    self::$_custom_fields['postal_code'],
                    self::$_custom_fields['city'],
                    self::$_custom_fields['country'],
                    self::$_custom_group_id,
                    $this->Id,
                    self::$_custom_fields['status']
                    );
    $result = CRM_Core_DAO::executeQuery($query);
    $lastId = CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID();');
    CRM_Donrec_Logic_ReceiptItem::createCopyAll($this->Id, $lastId);
    return TRUE;
  }

  /**
   * Delete this receipt.
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function delete(&$parameters) {
    self::getCustomFields();
    $status = empty($parameters['status']) ? 'ORIGINAL' : $parameters['status'];
    $query = "DELETE FROM `civicrm_value_donation_receipt_%d` WHERE `id` = %d";
    $query = sprintf($query, self::$_custom_group_id, $this->Id);
    $result = CRM_Core_DAO::executeQuery($query, $status);

    CRM_Donrec_Logic_ReceiptItem::deleteAll($this->Id);

    return TRUE;
  }

  /**
   * Mark this receipt as invalid
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function markInvalid(&$parameters) {
    $query = "UPDATE civicrm_value_donation_receipt_%d SET `%s` = 'INVALID' WHERE `id` = %d";
    $query = sprintf($query, self::$_custom_group_id, self::$_custom_fields['status'], $this->Id);
    $result = CRM_Core_DAO::executeQuery($query);
    return TRUE;
  }

  /**
   * Mark this receipt as withdrawn
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function markWithdrawn(&$parameters) {
    CRM_Donrec_Logic_ReceiptItem::setStatusAll($this->Id,"WITHDRAWN");
    $query = "UPDATE civicrm_value_donation_receipt_%d SET `%s` = 'WITHDRAWN' WHERE `id` = %d";
    $query = sprintf($query, self::$_custom_group_id, self::$_custom_fields['status'], $this->Id);
    $result = CRM_Core_DAO::executeQuery($query);
    return TRUE;
  }

  /**
   * Get all the properties of this receipt needed for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * Remark: we should start with a basic set of properties, and gradually extend as we go along
   *
   * @return an array of all properties needed for display
   */
  public function getDisplayProperties() {
    CRM_Donrec_Logic_ReceiptItem::getCustomFields();

    $query = "SELECT
              `%s` as `type`,
              `%s` as `status`,
              `%s` as `issued_on`,
              file.`uri` as `original_file`,
              SUM(item.`%s`) as `total_amount`,
              MIN(item.`%s`) as `date_from`,
              MAX(item.`%s`) as `date_to`,
              item.`%s` as `currency`
              FROM `civicrm_value_donation_receipt_%d` as receipt
              RIGHT JOIN `civicrm_value_donation_receipt_item_%d` as item
                ON item.`%s` = receipt.id
                AND item.`%s` = receipt.`%s`
              LEFT JOIN `civicrm_file` as file
                ON file.`id` = receipt.`%s`
              WHERE receipt.id = %d;";

    $query = sprintf($query,
      self::$_custom_fields['type'],
      self::$_custom_fields['status'],
      self::$_custom_fields['issued_on'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['total_amount'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['issued_on'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['issued_on'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['currency'],
      self::$_custom_group_id,
      CRM_Donrec_Logic_ReceiptItem::$_custom_group_id,
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['issued_in'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['status'],
      self::$_custom_fields['status'],
      self::$_custom_fields['original_file'],
      $this->Id
      );

    $result = CRM_Core_DAO::executeQuery($query);
    $display_properties = array();

    while($result->fetch()) {
      $display_properties['type'] = $result->type;
      $display_properties['status'] = $result->status;
      $display_properties['issued_on'] = $result->issued_on;
      $display_properties['original_file'] = $config->userFrameworkBaseURL
      . "sites/default/files/civicrm/custom/" . basename($result->original_file);
      $display_properties['total_amount'] = $result->total_amount;
      $display_properties['date_from'] = $result->date_from;
      $display_properties['date_to'] = $result->date_to;
      $display_properties['currency'] = $result->currency;
    }

    return $display_properties;
  }

  /**
   * Get all properties of this receipt, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * Remark: we should start with a basic set of properties, and gradually extend as we go along
   *
   * @return an array of all properties
   */
  public function getAllProperties() {
    CRM_Donrec_Logic_ReceiptItem::getCustomFields();

    $query = "SELECT
              `%s` as `status`,
              `%s` as `issued_on`,
              SUM(item.`%s`) as `total_amount`,
              MIN(item.`%s`) as `date_from`,
              MAX(item.`%s`) as `date_to`
              FROM `civicrm_value_donation_receipt_%d` as receipt
              RIGHT JOIN `civicrm_value_donation_receipt_item_%d` as item
                ON item.`%s` = receipt.id
                AND item.`%s` = receipt.`%s`
              WHERE receipt.id = %d;";

    $query = sprintf($query,
      self::$_custom_fields['status'],
      self::$_custom_fields['issued_on'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['total_amount'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['issued_on'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['issued_on'],
      self::$_custom_group_id,
      CRM_Donrec_Logic_ReceiptItem::$_custom_group_id,
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['issued_in'],
      CRM_Donrec_Logic_ReceiptItem::$_custom_fields['status'],
      self::$_custom_fields['status'],
      $this->Id
      );

    $result = CRM_Core_DAO::executeQuery($query);
    $display_properties = array();

    while($result->fetch()) {
      $display_properties['status'] = $result->status;
      $display_properties['issued_on'] = $result->issued_on;
      $display_properties['total_amount'] = $result->total_amount;
      $display_properties['date_from'] = $result->date_from;
      $display_properties['date_to'] = $result->date_to;
    }

    return $display_properties;
  }

  /**
   * Find all receipts for the given contact ID
   *
   * @param $contact_id    a contact ID
   * @param $parameters    TODO: to be definied. Maybe for only to restrict search (like 'only copies')
   *
   * @return an array of CRM_Donrec_Logic_Receipt instances
   */
  public static function getReceiptsForContact($contact_id, &$parameters) {
    self::getCustomFields();
    $query = "SELECT `id` FROM `civicrm_value_donation_receipt_%d` WHERE `entity_id` = %d ORDER BY `%s` DESC;";
    $query = sprintf($query, self::$_custom_group_id, $contact_id, self::$_custom_fields['issued_on']);
    $results = CRM_Core_DAO::executeQuery($query);
    $receipts = array();

    while($results->fetch()) {
      $receipts[] = new self($results->id);
    }

    return $receipts;
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
    $query = "SELECT COUNT(`id`) FROM `civicrm_value_donation_receipt_%d` WHERE `entity_id` = %d";
    $query = sprintf($query, self::$_custom_group_id, $contact_id);
    return CRM_Core_DAO::singleValueQuery($query);
  }

  //TODO: for what do we need this method?
  /**
   * Checks if there is a VALID donation receipt for the given contribution
   *
   * This method should be HIGHLY optimized
   *
   * @return TRUE if there is a VALID donation reciept, FALSE otherwise
   */
  public static function isContributionLocked($contribution_id) {
    self::getCustomFields();
    CRM_Donrec_Logic_ReceiptItem::getCustomFields();

    $query = "SELECT count(receipt.`id`)
              FROM `civicrm_value_donation_receipt_%d` as receipt
              INNER JOIN `civicrm_value_donation_receipt_item_%d` as item
              ON item.`%s` = receipt.`id`
              AND SHA1(CONCAT(item.`entity_id`, item.`%s`, item.`%s`, item.`%s`, item.`%s`, item.`%s`, item.`%s`, item.`%s`, item.`%s`, item.`%s`)) = item.`%s`
              AND item.`%s` <> 'INVALID'
              WHERE item.`entity_id` = %d
              AND receipt.`%s` <> 'INVALID'";

    $query = sprintf($query,
                    self::$custom_group_id,
                    CRM_Donrec_Logic_ReceiptItem::$custom_group_id,
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['issued_in'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['status'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['type'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['issued_in'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['issued_by'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['total_amount'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['non_deductible_amount'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['currency'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['issued_on'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['receive_date'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['contribution_hash'],
                    CRM_Donrec_Logic_ReceiptItem::$custom_fields['status'],
                    $contribution_id,
                    self::$custom_fields['status']
                    );
    return CRM_Core_DAO::singleValueQuery($query);
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
    $gid = self::$_custom_group_id;
    return (bool)CRM_Core_DAO::singleValueQuery(
      "SELECT EXISTS(SELECT 1 FROM `civicrm_value_donation_receipt_$gid`
       WHERE `id` = %1);", array(1 => array($this->Id, 'Integer')));
  }

  /**
  * Checks whether this receipt is an original
  * @return bool TRUE if the receipt is an original otherwise FALSE
  */
  public function isOriginal() {
    $gid = self::$_custom_group_id;
    $status = self::$_custom_fields['status'];
    return (bool)CRM_Core_DAO::singleValueQuery(
      "SELECT EXISTS(SELECT 1 FROM `civicrm_value_donation_receipt_$gid`
       WHERE `id` = %1 AND `$status` = 'ORIGINAL');", array(1 => array($this->Id, 'Integer')));
  }

  /**
  * Updates the class attribute to contain all custom fields of the
  * donation receipt database table.
  */
  protected static function getCustomFields() {
    if (self::$_custom_fields === NULL) {
      // get the ids of all relevant custom fields
      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'name' => 'zwb_donation_receipt',
      );
      $custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
      if (isset($custom_group['is_error'])) {
        error_log(sprintf('de.systopia.donrec: getCustomFields: error: %s', $custom_group['error_message']));
        return NULL;
      }

      self::$_custom_group_id = $custom_group['id'];

      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'custom_group_id' => $custom_group['id'],
      );
      $custom_fields = civicrm_api('CustomField', 'get', $params);
      if ($custom_fields['is_error'] != 0) {
        error_log(sprintf('de.systopia.donrec: getCustomFields: error: %s', $custom_fields['error_message']));
        return NULL;
      }

      self::$_custom_fields = array();
      foreach ($custom_fields['values'] as $field) {
        self::$_custom_fields[$field['name']] = $field['column_name'];
      }
    }
  }
}
