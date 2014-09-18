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
  * Constructor
  */
  protected function __construct() {
    // initialize custom field map
    self::getCustomFields();
  }

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
    $line = $snapshot->getLine($snapshot_line_id);
    if (empty($line)) {
      $parameters['is_error'] = "snapshot line #$snapshot_line_id does not exist";
      return FALSE;
    }

    $params = array('NULL', ); // FIXME: the second parameter has to be filled with the entity_id (contact id)
    $params['status'] = 'ORIGINAL';
    $params['type'] = 'SINGLE';
    $params['issued_on'] = $line['created_timestamp'];
    $params['issued_by'] = $line['created_by'];
    $params['original_file'] = -1; //TODO: create pdf?

    $gluedParams = implode(',', $params);
    
    $query = sprintf("INSERT INTO `civicrm_value_donation_receipt_%d` (`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`) 
                      VALUES (%s);",
                self::$_custom_group_id,
                self::$_custom_fields['status'],
                self::$_custom_fields['type'],
                self::$_custom_fields['issued_on'],
                self::$_custom_fields['issued_by'],
                self::$_custom_fields['original_file'],
                $gluedParams
              );

    $result = CRM_Core_DAO::executeQuery($query);

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
  public static function createBulkFromSnapshot($snapshot_line_ids, &$parameters) {
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

    $all_params = array('NULL', ); // FIXME: the second parameter has to be filled with the entity_id (contact id)
    foreach ($lines as $line) {
      $params = array();
      $params['status'] = 'ORIGINAL';
      $params['type'] = 'BULK';
      $params['issued_on'] = $line['created_timestamp'];
      $params['issued_by'] = $line['created_by'];
      $params['original_file'] = -1; //TODO: create pdf?

      $gluedParams = implode(',', $params); 
      $all_params[] = "($gluedParams)";
    }
    $gluedDataString = implode(',', $all_params);

    $query = sprintf("INSERT INTO `civicrm_value_donation_receipt_%d` (`id`, `entity_id`, `%s`, `%s`, `%s`, `%s`, `%s`) 
                        VALUES %s;",
                  self::$_custom_group_id,
                  self::$_custom_fields['status'],
                  self::$_custom_fields['type'],
                  self::$_custom_fields['issued_on'],
                  self::$_custom_fields['issued_by'],
                  self::$_custom_fields['original_file'],
                  $gluedDataString
                );
    $result = CRM_Core_DAO::executeQuery($query);
    
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
  * creates and returns a new donation receipt object from the
  * given parameters
  *
  * @param params associative array of attribute name to value
  * @return receipt object OR error array
  * @deprecated TODO: @Niko: do we still need this?
  */
  public static function create(&$params) {
    $receipt = new self();
    foreach ($params as $key => $value) {
      $receipt->updateByName($key, $value, $receipt);
    }
    return $receipt;
  }



  /**
  * returns a donation receipt object from the
  * given contact
  *
  * @param $contact_id
  * @param $receipt_id
  * @return receipt object OR NULL
  * @deprecated TODO: @Niko: do we still need this?
  */
  public static function getSingle($contact_id, $receipt_id) {
    if($contact_id === NULL || $receipt_id === NULL) {
      return NULL;
    }

    // get all custom values for the specified contact
    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'entity_id' => $contact_id,
    );
    $custom_values = civicrm_api('CustomValue', 'get', $params);
    if ($custom_values['is_error'] != 0) {
      error_log(sprintf('de.systopia.donrec: receipt: error: %s', $custom_values['error_message']));
      return NULL;
    }

    // get the ids of all relevant custom fields
    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'name' => 'zwb_donation_receipt',
    );
    $custom_group = civicrm_api('CustomGroup', 'getsingle', $params);
    if (isset($custom_group['is_error'])) {
      error_log(sprintf('de.systopia.donrec: receipt: error: %s', $custom_group['error_message']));
      return NULL;
    }

    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'custom_group_id' => $custom_group['id'],
    );
    $custom_fields = civicrm_api('CustomField', 'get', $params);
    if ($custom_fields['is_error'] != 0) {
      error_log(sprintf('de.systopia.donrec: receipt: error: %s', $custom_fields['error_message']));
      return NULL;
    }

    $relevant_ids = array();
    $id_to_name = array();
    foreach ($custom_fields['values'] as $field) {
      $relevant_ids[$field['name']] = $field['id'];
      $id_to_name[$field['id']] = $field['name'];
    }

    //error_log("relevant ids " . print_r($relevant_ids, TRUE));

    $receipt = new self();
    // filter
    foreach ($custom_values['values'] as $value_group) {
      if(in_array($value_group['id'], $relevant_ids)) {
        $this->updateByName($id_to_name[$value_group['id']], $value_group[$receipt_id], $receipt);
      }
    }

    return $receipt;
  }

  /**
   * returns a donation receipt object from the
   * given contact
   *
   * @param $contact_id
   * @param $receipt_id
   * @return receipt object, array of receipt objects or NULL
   * @deprecated TODO: @Niko: do we still need this?
   */
  public static function get($contact_id, $receipt_id) {
    if ($contact_id === NULL) {
      return NULL;
    }

    if ($receipt_id === NULL) {
      // get all
      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'custom_group_name' => 'zwb_donation_receipt',
      );
      $result = civicrm_api('CustomGroup', 'get', $params);
      if ($result['is_error'] != 0) {
        error_log(sprintf('de.systopia.donrec: receipt: error: %s', $result['error_message']));
        return NULL;
      }elseif ($result['count'] < 1) {
        error_log(sprintf('de.systopia.donrec: receipt: error: custom group not found'));
        return NULL;
      }

      $table_name = "";
      foreach ($result['values'] as $r) {
        if($r['name'] == 'zwb_donation_receipt') {
          $table_name = $r['table_name'];
        }
      }

      $query = "SELECT `id` FROM `$table_name` WHERE `entity_id` = %1;";
      // prepare parameters 
      $params = array(1 => array($contact_id, 'Integer'));

      // execute the query
      $result = CRM_Core_DAO::executeQuery($query, $params);
      $ids = array();
      while ($result->fetch()) {
        $ids[] = $result->id;
      }

      $receipts = array();
      foreach ($ids as $id) {
        $receipts[] = self::getSingle($contact_id, $id);
      }

      return $receipts;
    }else{
      // get single
      return self::getSingle($entity_id, $receipt_id);
    }
    
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