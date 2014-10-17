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
 * This class represents a single donation receipt item
 */
class CRM_Donrec_Logic_ReceiptItem {
  /**
  * Custom field array to map attribute names to database colums
  * i.e. self::$_custom_field['total_amount'] == 10
  */
  public static $_custom_fields; // TODO: set private, but add getters
  public static $_custom_group_id;

  /**
  * Creates a new receipt item
  * @param array of parameters
  * @return TRUE or FALSE if there was an error
  */
  public static function create(&$params) {
  	self::getCustomFields();

  	$query = sprintf("INSERT INTO `civicrm_value_donation_receipt_item_%d`
  		(`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`)
  		VALUES (NULL, %%d, %%s, %%s, %%d, %s, %%d, %%f, %%f, %%s, %s, %%s);",
	  		self::$_custom_group_id,
	        self::$_custom_fields['status'],
        	self::$_custom_fields['type'],
        	self::$_custom_fields['issued_in'],
        	self::$_custom_fields['issued_on'],
        	self::$_custom_fields['issued_by'],
        	self::$_custom_fields['total_amount'],
        	self::$_custom_fields['non_deductible_amount'],
        	self::$_custom_fields['currency'],
        	self::$_custom_fields['receive_date'],
        	self::$_custom_fields['contribution_hash'],
        	"'" . $params['issued_on'] . "'",
        	"'" . $params['receive_date'] . "'"
        );

    $query = sprintf($query,
                     $params['contribution_id'],
                     empty($params['status']) ? "NULL" : "'" . $params['status']. "'",
                     empty($params['type']) ? "NULL" : "'" . $params['type'] . "'",
                     $params['issued_in'],
                     $params['issued_by'],
                     is_null($params['total_amount']) ? 0.00 : $params['total_amount'],
                     is_null($params['non_deductible_amount']) ? 0.00 : $params['non_deductible_amount'],
                     empty($params['currency']) ? "NULL" : "'" . $params['currency'] . "'" ,
                     empty($params['contribution_hash']) ? "NULL" : "'" . $params['contribution_hash']. "'"
                     );


    $result = CRM_Core_DAO::executeQuery($query);

    return FALSE;
  }

  /**
  * Creates a copy of all donation receipt items of a specific donation receipt
  * @param int donation receipt id
  * @param int id of the copy
  */
  public static function createCopyAll($donation_receipt_id, $donation_receipt_copy_id) {
    self::getCustomFields();
    $sha1_string = "SHA1(CONCAT(`entity_id`, 'COPY', `%s`, $donation_receipt_copy_id, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`))";
    $sha1_string = sprintf($sha1_string,
                          self::$_custom_fields['type'],
                          self::$_custom_fields['issued_in'],
                          self::$_custom_fields['issued_by'],
                          self::$_custom_fields['total_amount'],
                          self::$_custom_fields['non_deductible_amount'],
                          self::$_custom_fields['currency'],
                          self::$_custom_fields['issued_on'],
                          self::$_custom_fields['receive_date'],
                          self::$_custom_group_id);

    $query = "INSERT INTO `civicrm_value_donation_receipt_item_%d`
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
      `%s`)
    SELECT NULL as`id`,
    `entity_id`,
    'COPY' as `%s`,
    `%s`,
    $donation_receipt_copy_id as `%s`,
    NOW() as `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
    `%s`,
   %s as `%s`
    FROM `civicrm_value_donation_receipt_item_%d`
    WHERE `%s` = %d AND `%s` = 'ORIGINAL';";
    $query = sprintf($query,
      self::$_custom_group_id,
      self::$_custom_fields['status'],
      self::$_custom_fields['type'],
      self::$_custom_fields['issued_in'],
      self::$_custom_fields['issued_on'],
      self::$_custom_fields['issued_by'],
      self::$_custom_fields['total_amount'],
      self::$_custom_fields['non_deductible_amount'],
      self::$_custom_fields['currency'],
      self::$_custom_fields['receive_date'],
      self::$_custom_fields['contribution_hash'],
      self::$_custom_fields['status'],
      self::$_custom_fields['type'],
      self::$_custom_fields['issued_in'],
      self::$_custom_fields['issued_on'],
      self::$_custom_fields['issued_by'],
      self::$_custom_fields['total_amount'],
      self::$_custom_fields['non_deductible_amount'],
      self::$_custom_fields['currency'],
      self::$_custom_fields['receive_date'],
      $sha1_string,
      self::$_custom_fields['contribution_hash'],
      self::$_custom_group_id,
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
    if (!empty($status)) {
      $statusString = sprintf(" AND `%s` = '%s'", self::$_custom_fields['status'], $status);
    }else{
      $statusString = "";
    }

    $query = "DELETE FROM `civicrm_value_donation_receipt_item_%d` WHERE `%s` = %d%s;";
    $query = sprintf($query,
                    self::$_custom_group_id,
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
    $query = "UPDATE `civicrm_value_donation_receipt_item_%d` SET `%s` = %%1 WHERE `%s` = %d;";
    $query = sprintf($query,
                    self::$_custom_group_id,
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
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'name' => 'zwb_donation_receipt_item',
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
