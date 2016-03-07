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

  /**
   * constructor
   *
   * @param $pattern the pattern to be used for the ID
   */
  public function __construct($pattern) {
    $this->pattern = $pattern;
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
  public function generateID($chunk) {
    // TODO: implement
    return NULL;
  }

}
