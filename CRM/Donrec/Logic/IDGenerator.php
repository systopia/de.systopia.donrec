<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: T. Leichtfuß (leichtfuss -at- systopia.de)     |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class represents generator function for unique IDs
 * based on a pattern string
 */
class CRM_Donrec_Logic_IDGenerator {

  /** the pattern to be used for the ID generation */
  protected $pattern = 'superID_{serial}_{issue_year}';
  protected $tokens = array(
    'issue_year' => NULL,
    'contact_id' => NULL
  );
  /**
   * constructor
   *
   * @param $pattern the pattern to be used for the ID
   */
  public function __construct($pattern) {
    // $this->pattern = $pattern;
  }


  /**
   * You need to lock the generator so a truely unique ID can be generated
   */
  public function lock() {
    // TODO: implement
  }


  /**
   * Once you're done you have to release
   *  a previously locked generator
   */
  public function release() {
    // TODO: implement
  }

  /**
   * generate a new, unique ID with the pattern passed in the constructor
   *
   * The generator needs to be locked before this can happen.
   *
   * @param $chunk the set of contributions used for this receipt as used in CRM_Donrec_Logic_Engine
   * @return unique ID string
   */
  public function generateID($snapshot_lines) {

    // prepare tokens
    $contact_id = $snapshot_lines[0]['contact_id'];
    $this->tokens['contact_id'] = $snapshot_lines[0]['contact_id'];
    $this->tokens['issue_year'] = date("Y");

    // prepare pattern and regexp
    $pattern = $this->pattern;
    foreach ($this->tokens as $token => $value) {
      $pattern = str_replace("{" . $token . "}", $value, $pattern);
    }
    $regexp = '^' . str_replace("{serial}", "[0-9]+", $pattern) . '$';
    $serial_pos = strpos($pattern, "{serial}") + 1;
    $serial_suffix = substr($pattern, $serial_pos - 1 + strlen('{serial}'));

    // get database-infos
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');
    $field = $fields['receipt_id'];

    // build and run query
    $query = "
      SELECT MAX(SUBSTRING(`$field` FROM $serial_pos FOR LOCATE('$serial_suffix', `$field`) - $serial_pos))
      FROM `$table`
      WHERE `$field` REGEXP '$regexp'
    ";
    $last_serial = CRM_Core_DAO::singleValueQuery($query);

    // prepare receipt_id
    if ($last_serial) {
      $receipt_id = str_replace('{serial}', $last_serial + 1, $pattern);
    } else {
      $receipt_id = str_replace('{serial}', 1, $pattern);
    }
    // error_log($receipt_id);

    return $receipt_id;
  }

}
