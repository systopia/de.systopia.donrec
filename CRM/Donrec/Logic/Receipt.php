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
  private $status;
  private $type;
  private $issued_on;
  private $issued_by;
  private $original_file;

  protected static $_custom_fields;

  public function __construct() {
    self::getCustomFields();
  }

  /**
  * creates and returns a new donation receipt object from the
  * given parameters
  *
  * @param params associative array of attribute name to value
  * @return receipt object OR error array
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
  * updates an attribute
  * @param name name of the attribute
  * @param value new value for the selected attribute
  * @param target updates a receipt object or the current one if NULL
  * @return void
  */
  private function updateByName($name, $value, $target = NULL) {
    $receipt = empty($target) ? $this : $target;
    switch ($name) {
      case 'status':
        $receipt->setStatus($value);
        break;
      case 'type':
        $receipt->setType($value);
        break;
      case 'issued_on':
        $receipt->setIssuedOn($value);
        break;
      case 'issued_by':
        $receipt->setIssuedBy($value);
        break;
      case 'original_file':
        $receipt->setOriginalFile($value);
        break;
      default:
        break;
    }
  }

  public static function getCustomFields() {
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


  public function getStatus() {
    return $this->status;
  }
  public function getType() {
    return $this->type;
  }
  public function getIssuedOn() {
    return $this->issued_on;
  }
  public function getIssuedBy() {
    return $this->issued_by;
  }
  public function getOriginalFile() {
    return $this->original_file;
  }


  public function setStatus($newStatus) {
    $this->status = $newStatus;
  }
  public function setType($newType) {
    $this->type = $newType;
  }
  public function setIssuedOn($newDate) {
    $this->issued_on = $newDate;
  }
  public function setIssuedBy($newDate) {
    $this->issued_by = $newDate;
  }
  public function setOriginalFile($newFile) {
    $this->original_file = $newFile;
  }
}