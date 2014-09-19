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
  * Creates a new receipt with the given snapshot line
  *
  * @param $snapshot           a snapshot object
  * @param $snapshot_line_id   the ID of the snapshot line to be used for creation
  * @param $parameters         an assoc. array of creation parameters TODO: to be defined
  *
  * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
  */
  public static function createSingleFromSnapshot($snapshot, $snapshot_line_id, &$parameters) {
    // initialize custom field map
    self::getCustomFields();

    $line = $snapshot->getLine($snapshot_line_id);
    if (empty($line)) {
      $parameters['is_error'] = "snapshot line #$snapshot_line_id does not exist";
      return FALSE;
    }
    
    $query = sprintf("INSERT INTO `civicrm_value_donation_receipt_%d` (`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`,  `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`) 
                      VALUES (NULL, %%1, %%2, %%3, %s, %%5, %%6, %%7, %%8, %%9, %%10, %%11, %%12, %%13);",
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

    $query_params = array(
        1 => array($line['contact_id'], 'Integer'),
        2 => array('ORIGINAL', 'String'),
        3 => array('SINGLE', 'String'),
        5 => array($line['created_by'], 'Integer'),
        6 => array(-1, 'Integer'), //TODO: create pdf?
        7 => array(empty($line['street_address']) ? "": $line['street_address'], 'String'),
        8 => array(empty($line['supplemental_address_1']) ? "" : $line['supplemental_address_1'], 'String'),
        9 => array(empty($line['supplemental_address_2']) ? "" : $line['supplemental_address_2'], 'String'),
        10 => array(empty($line['supplemental_address_3']) ? "" : $line['supplemental_address_3'], 'String'),
        11 => array($line['postal_code'], 'Integer'),
        12 => array($line['city'], 'String'),
        13 => array($line['country'], 'String')
      );

    $result = CRM_Core_DAO::executeQuery($query, $query_params);
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

    return TRUE;
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
    if (count($lines) < 2) {
      $parameters['is_error'] = "this is no bulk donation receipt";
      return FALSE;
    }

    // initialize custom field map
    self::getCustomFields();

    $line = $lines[0];
    if (empty($line)) {
      $parameters['is_error'] = "snapshot line #$snapshot_line_id does not exist";
      return FALSE;
    }
    
    $query = sprintf("INSERT INTO `civicrm_value_donation_receipt_%d` (`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`,  `%s`, `%s`, `%s`, `%s`, `%s`, `%s`, `%s`) 
                      VALUES (NULL, %%1, %%2, %%3, %s, %%5, %%6, %%7, %%8, %%9, %%10, %%11, %%12, %%13);",
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

    $query_params = array(
        1 => array($line['contact_id'], 'Integer'),
        2 => array('ORIGINAL', 'String'),
        3 => array('BULK', 'String'),
        5 => array($line['created_by'], 'Integer'),
        6 => array(-1, 'Integer'), //TODO: create pdf?
        7 => array(empty($line['street_address']) ? "": $line['street_address'], 'String'),
        8 => array(empty($line['supplemental_address_1']) ? "" : $line['supplemental_address_1'], 'String'),
        9 => array(empty($line['supplemental_address_2']) ? "" : $line['supplemental_address_2'], 'String'),
        10 => array(empty($line['supplemental_address_3']) ? "" : $line['supplemental_address_3'], 'String'),
        11 => array($line['postal_code'], 'Integer'),
        12 => array($line['city'], 'String'),
        13 => array($line['country'], 'String')
      );

    $result = CRM_Core_DAO::executeQuery($query, $query_params);
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

    return TRUE; 
  }  

  /**
   * Creates a copy of this receipt. The receipt status will be 'COPY'
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function createCopy(&$parameters) {
    // TODO: @Niko implement.
    return FALSE;
  }

  /**
   * Delete this receipt.
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function delete(&$parameters) {
    // TODO: @Niko implement.
    return FALSE;
  }

  /**
   * Mark this receipt as invalid
   *
   * @param $parameters         an assoc. array of creation parameters TODO: to be defined
   *
   * @return TRUE if successfull, FALSE otherwise. In that case, the $parameters['error'] contains an error message
   */
  public function markInvalid(&$parameters) {
    // TODO: @Niko implement.
    return FALSE;
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
    // TODO: @Niko implement.
    return array();
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
    // TODO: @Niko implement.
    return array();
  }

  /**
   * Find all receipts for the given contact ID
   *
   * @param $contact_id    a contact ID
   * @param $parameters    TODO: to be definied. Maybe for only to restrict search (like 'only copies')
   *
   * @return an array of CRM_Donrec_Logic_Receipt instances
   */
  public function getReceiptsForContact($contact_id, &$parameters) {
    // TODO: @Niko implement.
    return array();
  }

  /**
   * Checks if there is a VALID donation receipt for the given contribution
   * 
   * This method should be HIGHLY optimized
   *
   * @return TRUE if there is a VALID donation reciept, FALSE otherwise
   */
  public static function isContributionLocked($contribution_id) {
    // TODO: @Niko implement.
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