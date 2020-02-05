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
 * This class represents a single donation receipt item
 */
class CRM_Donrec_Logic_ReceiptItem {
  /**
  * Custom field array to map attribute names to database colums
  * i.e. self::$_custom_field['total_amount'] == 10
  */
  public static $_custom_fields; // TODO: set private, but add getters
  public static $_custom_group_id;
  public static $_checksum_keys = array(
    'contribution_id',
    'status',
    'type',
    'issued_in',
    'receipt_id',
    'issued_by',
    'total_amount',
    'non_deductible_amount',
    'currency',
    'issued_on',
    'receive_date'
  );


  /**
   * Creates a new receipt item
   *
   * @param array $params array of parameters
   *
   * @return \CRM_Core_DAO | bool
   *   FALSE if there was an error //TODO
   * @throws \CRM_Core_Exception
   */
  public static function create($params) {
    self::getCustomFields();
    $fields = self::$_custom_fields;
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $params['contribution_hash'] = self::calculateChecksum($params);

    // build set-string
    $set_str = "`entity_id`=$params[contribution_id]";
    foreach ($fields as $key => $field) {
      if (!is_null($params[$key])) {
        $value = CRM_Utils_DonrecHelper::escapeString($params[$key]);
        $set_str .= ", `$field`='$value'";
      }
    }

    // build query
    $query = "INSERT INTO `$table` SET $set_str";

    // run query
    $result = CRM_Core_DAO::executeQuery($query);
    return $result;
  }

  /**
  * Calculate sha1 checksum
  * @param array params
  * @return string checksum
  */
  public static function calculateChecksum($params) {
    $str = '';
    foreach (self::$_checksum_keys as $key) {
      $str .= $params[$key];
    }
    return sha1($str);
  }

  /**
  * Creates a copy of all donation receipt items of a specific donation receipt
  * @param int donation receipt id
  * @param int id of the copy
  */
  public static function createCopyAll($donation_receipt_id, $donation_receipt_copy_id) {
    // TODO: make a generic version of this, using the fields defined in CRM_Donrec_DataStructure
    self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $sha1_string = "SHA1(CONCAT(`entity_id`, 'COPY', `%s`, $donation_receipt_copy_id, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`))";
    $sha1_string = sprintf($sha1_string,
                          self::$_custom_fields['type'],
                          self::$_custom_fields['issued_in'],
                          self::$_custom_fields['receipt_id'],
                          self::$_custom_fields['issued_by'],
                          self::$_custom_fields['total_amount'],
                          self::$_custom_fields['non_deductible_amount'],
                          self::$_custom_fields['financial_type_id'],
                          self::$_custom_fields['currency'],
                          self::$_custom_fields['issued_on'],
                          self::$_custom_fields['receive_date'],
                          self::$_custom_group_id);

    $query = "INSERT INTO `$receipt_item_table`
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
    SELECT NULL as `id`,
    `entity_id`,
    'COPY' as `%s`,
    `%s`,
    $donation_receipt_copy_id as `%s`,
    `%s`,
    NOW() as `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
   %s as `%s`
    FROM `$receipt_item_table`
    WHERE `%s` = %d AND `%s` = 'ORIGINAL';";
    $query = sprintf($query,
      // for spec part
      self::$_custom_fields['status'],
      self::$_custom_fields['type'],
      self::$_custom_fields['issued_in'],
      self::$_custom_fields['receipt_id'],
      self::$_custom_fields['issued_on'],
      self::$_custom_fields['issued_by'],
      self::$_custom_fields['total_amount'],
      self::$_custom_fields['non_deductible_amount'],
      self::$_custom_fields['currency'],
      self::$_custom_fields['financial_type_id'],
      self::$_custom_fields['receive_date'],
      self::$_custom_fields['contribution_hash'],
      // for VALUES part
      self::$_custom_fields['status'],
      self::$_custom_fields['type'],
      self::$_custom_fields['issued_in'],
      self::$_custom_fields['receipt_id'],
      self::$_custom_fields['issued_on'],
      self::$_custom_fields['issued_by'],
      self::$_custom_fields['total_amount'],
      self::$_custom_fields['non_deductible_amount'],
      self::$_custom_fields['currency'],
      self::$_custom_fields['financial_type_id'],
      self::$_custom_fields['receive_date'],
      $sha1_string,
      self::$_custom_fields['contribution_hash'],
      self::$_custom_fields['issued_in'],
      $donation_receipt_id,
      self::$_custom_fields['status']
      );
    $result = CRM_Core_DAO::executeQuery($query);
  }

  /**
  * Deletes all contribution items for a specific donation receipt
  * @param int donation receipt id
  * @param string filter by status (deletes all including copies if not specified)
  */
  public static function deleteAll($donation_receipt_id, $status = NULL) {
    self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    if (!empty($status)) {
      $statusString = sprintf(" AND `%s` = '%s'", self::$_custom_fields['status'], $status);
    }else{
      $statusString = "";
    }

    $query = "DELETE FROM `$receipt_item_table` WHERE `%s` = %d%s;";
    $query = sprintf($query,
                    self::$_custom_fields['issued_in'],
                    $donation_receipt_id,
                    $statusString);
    $result = CRM_Core_DAO::executeQuery($query);
  }

   /**
  * Sets status of all contribution items for a specific donation receipt
  * @param int donation receipt id
  * @param string status
  */
  public static function setStatusAll($donation_receipt_id, $status = "WITHDRAWN") {
    self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $query = "UPDATE `$receipt_item_table` SET `%s` = %%1 WHERE `%s` = %d;";
    $query = sprintf($query,
                    self::$_custom_fields['status'],
                    self::$_custom_fields['issued_in'],
                    $donation_receipt_id
                    );
    $params = array(1 => array($status,'String'));
    $result = CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
  * Updates the class attribute to contain all custom fields of the
  * donation receipt database table.
  */
  public static function getCustomFields() {
    if (self::$_custom_fields === NULL) {
      // get the ids of all relevant custom fields
      $params = array(
        'version'    => 3,
        'name'       => 'zwb_donation_receipt_item',
      );
      $custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
      if (isset($custom_group['is_error'])) {
        CRM_Core_Error::debug_log_message(sprintf('de.systopia.donrec: getCustomFields: error: %s', $custom_group['error_message']));
        return NULL;
      }

      self::$_custom_group_id = $custom_group['id'];

      $params = array(
        'version'         => 3,
        'option.limit'    => 999,
        'custom_group_id' => $custom_group['id'],
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
   * Check if a contribution has a receipt-item with status ORIGINAL.
   *
   * return boolean or item id
   * @param int $contribution_id
   * @param bool $return_id
   * @return bool|string|null
*/
  public static function hasValidReceiptItem($contribution_id, $return_id=FALSE) {
    $contribution_id = (int) $contribution_id;
    if (empty($contribution_id)) return FALSE;    // prevent SQL errors
    
    self::getCustomFields();
    $receipt_item_table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt_item');
    $status_field = self::$_custom_fields['status'];

    $query = "
      SELECT `id`
      FROM `$receipt_item_table`
      WHERE `entity_id` = $contribution_id
      AND `$status_field` = 'ORIGINAL'";
    $result = CRM_Core_DAO::singleValueQuery($query);

    if($return_id && !is_null($result)) {
      return $result;
    }else{
      return !is_null($result);
    }
  }
}
