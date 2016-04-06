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
  protected $pattern = NULL;
  protected $is_test = NULL;
  protected $serial_regexp = "{serial(:[^}]+)?}";
  protected $tokens = array(
    'issue_year' => NULL,
    'contact_id' => NULL
  );
  /**
   * constructor
   *
   * @param $pattern the pattern to be used for the ID
   */
  public function __construct($pattern, $is_test) {
    # serial-token must occur exactly one time
    $serial_count_regexp = '/' . $this->serial_regexp . '/';
    $count = preg_match_all($serial_count_regexp, $pattern);

    # tokens must be separated and not be next to numbers
    $invalid_token_regex = '/([0-9}]{[^}]+}|{[^}]+}[0-9{])/';
    $invalid = preg_match($invalid_token_regex, $pattern);

    if ($count != 1 || $invalid) {
      $msg = "Invalid ID-pattern: '$pattern'";
      error_log($msg);
      throw new Exception($msg);
    }
    $this->pattern = $pattern;
    $this->is_test = $is_test;
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
    // FIXME: check for occurance
    $contact_id = $snapshot_lines[0]['contact_id'];
    $snapshot_line = (isset($snapshot_lines['id']))? $snapshot_lines : $snapshot_lines[0];
    $this->tokens['contact_id'] = $snapshot_line['contact_id'];
    $this->tokens['issue_year'] = date("Y");

    // get database-infos
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');
    $field = $fields['receipt_id'];

    // prepare pattern and regexp
    $serial_regexp = '/' . $this->serial_regexp . '/';
    $pattern = $this->pattern;
    foreach ($this->tokens as $token => $value) {
      $pattern = str_replace("{" . $token . "}", $value, $pattern);
    }

    if ($this->is_test) {
      return preg_replace($serial_regexp, "TEST", $pattern);
    }

    // get the length and position of the serial-token
    preg_match($serial_regexp, $pattern, $match, PREG_OFFSET_CAPTURE);
    $serial_token_length = strlen($match[0][0]);
    $serial_token_position = $match[0][1];

    // get everything behind the serial-token
    $serial_token_suffix = substr($pattern, $serial_token_position + $serial_token_length);

    // mysql counts from 1
    $serial_token_position++;

    // build the LOCATE-part of the query
    if ($serial_token_suffix) {
      $length_query = "FOR LOCATE('$serial_token_suffix', `$field`) - $serial_token_position";
    }

    // replace the token to get the mysql-regexp-string
    $mysql_regexp = '^' . preg_replace($serial_regexp, "[0-9]+", $pattern) . '$';

    // build and run query
    $query = "
      SELECT MAX(CAST(SUBSTRING(`$field` FROM $serial_token_position $length_query) AS UNSIGNED))
      FROM `$table`
      WHERE `$field` REGEXP '$mysql_regexp'
    ";
    $last_serial = CRM_Core_DAO::singleValueQuery($query);

    // prepare receipt_id
    if ($last_serial) {
      $receipt_id = preg_replace($serial_regexp, $last_serial + 1, $pattern);
    } else {
      $receipt_id = preg_replace($serial_regexp, 1, $pattern);
    }

    // check length of receipt-id
    if (strlen($receipt_id) > 64) {
      $msg = "Receipt-ID is too long (Maximum length is 64 chars): '$receipt_id'";
      error_log($msg);
      throw new Exception($msg);
    }

    return $receipt_id;
  }

}
