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
  protected static $_custom_fields;
  protected static $_custom_group_id;

  public function bla() {
  	self::getCustomFields();
  	print_r(self::$_custom_fields);
  }

  /**
  * Creates a new receipt item
  * @param array of parameters
  * @return TRUE or FALSE if there was an error
  */
  public static function create(&$params) {
  	self::getCustomFields();

  	$query = sprintf("INSERT INTO `civicrm_value_donation_receipt_item_%d` 
  		(`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`) 
  		VALUES (NULL, %%1, %%2, %%3, %%4, %s, %%5, %%6, %%7, %%8, %s, %%9);",
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

  	$query_params = array(
        1 => array($params['contribution_id'], 'Integer'),
        2 => array($params['status'], 'String'),
        3 => array($params['type'], 'String'),
        4 => array($params['issued_in'], 'Integer'),
        5 => array($params['issued_by'], 'Integer'),
        6 => array($params['total_amount'], 'Float'),
        7 => array($params['non_deductible_amount'], 'Float'),
        8 => array($params['currency'], 'String'),
        9 => array($params['contribution_hash'], 'String'),
    );

    $result = CRM_Core_DAO::executeQuery($query, $query_params);

    return FALSE;
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
