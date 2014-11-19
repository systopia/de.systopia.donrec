<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This class extends the current CiviCRM lock
 * by a security mechanism to prevent a process from
 * acquiring two or more locks.
 * This, due to the nature of the underlying implementation
 * would RELEASE the previously acquired lock
 */
class CRM_Utils_SafeLock extends CRM_Core_Lock {

  private static $_acquired_lock = NULL;

  public function acquire() {
    if ($this != self::$_acquired_lock && self::$_acquired_lock != NULL) {
      $other_lock_name = self::$_acquired_lock->_name;
      throw new Exception("This process cannot acquire more than one lock! It still owns lock '{$other_lock_name}'.");
    } else {
      $result = parent::acquire();
      if ($result) {
        self::$_acquired_lock = $this;
      }
      return $result;
    }
  }

  public function release() {
    if ($this != self::$_acquired_lock && self::$_acquired_lock != NULL) {
      $other_lock_name = self::$_acquired_lock->_name;
      throw new Exception("This process cannot release lock '{$this->_name}'! It still owns lock '{$other_lock_name}'.");
    } else {
      self::$_acquired_lock = NULL;
      return parent::release();
    }
  }
}