<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

/**
 * This class extends the current CiviCRM lock
 * by a security mechanism to prevent a process from
 * acquiring two or more locks.
 * This, due to the nature of the underlying implementation
 * would RELEASE the previously acquired lock
 */
class CRM_Utils_DonrecSafeLock {

  private static ?self $_acquired_lock = NULL;

  private CRM_Core_Lock $lock;
  private string $name;
  private int $counter;

  private function __construct(CRM_Core_Lock $civilock, string $lockname) {
    $this->lock = $civilock;
    $this->name = $lockname;
    $this->counter = 1;
  }

  public function getName() {
    return $this->name;
  }

  /**
   * Will acquire a lock with the given name,
   * if no other lock has been acquired by this process.
   *
   * If the same lock has been acquired before (and not been released),
   * in internal counter is increased. Therefore you can acquire the same
   * lock multiple times, but you will then have to release them
   * the same amount of times
   *
   * @param string $name
   * @param int $timeout
   *
   * @return \CRM_Utils_DonrecSafeLock | NULL
   *   a SafeLock instance or NULL if timed out
   * @throws \CRM_Core_Exception
   */
  public static function acquireLock($name, $timeout = 60) {
    if (self::$_acquired_lock == NULL) {
      // it's free, we'll try to take it
      $lock = new CRM_Core_Lock($name, $timeout);
      if ($lock->acquire()) {
        // we got it!
        self::$_acquired_lock = new CRM_Utils_DonrecSafeLock($lock, $name);
        return self::$_acquired_lock;
      }
      else {
        // timed out
        return NULL;
      }

    }
    elseif (self::$_acquired_lock->getName() == $name) {
      // this means acquiring 'our' lock again:
      $lock = self::$_acquired_lock;
      $lock->counter += 1;
      return $lock;

    }
    else {
      // this is the BAD case: somebody's trying to acquire ANOTHER LOCK,
      //  while we still own another one
      $lock_name = self::$_acquired_lock->getName();
      throw new Exception("This process cannot acquire more than one lock! It still owns lock '$lock_name'.");
    }

  }

  /**
   * Will release a lock with the given name,
   *  if it has been acquired before
   *
   * @param $name
   *
   * @throws \Exception
   */
  public static function releaseLock($name) {
    if (self::$_acquired_lock == NULL) {
      // weird, we don't own this lock...
      Civi::log()->debug("de.systopia.donrec: This process cannot release lock '$name', it has not been acquired.");
      throw new Exception("This process cannot release lock '$name', it has not been acquired.");

    }
    elseif (self::$_acquired_lock->getName() == $name) {
      // we want to release our own lock
      self::$_acquired_lock->release();

    }
    else {
      // somebody is trying to release ANOTHER LOCK
      $lock_name = self::$_acquired_lock->getName();
      Civi::log()->debug(
        "de.systopia.donrec: This process cannot realease lock '$name', it still owns lock '$lock_name'."
      );
      throw new Exception("This process cannot realease lock '$name', it still owns lock '$lock_name'.");
    }
  }

  /**
   * check if acquired
   */
  public function isAcquired() {
    return $this->lock->isAcquired();
  }

  /**
   * Will release a lock with the given name,
   *  if it has been acquired before
   */
  public function release() {
    if ($this->counter > 1) {
      // this is a lock that we acquired multiple times:
      //  simply decrease counter
      $this->counter -= 1;

    }
    elseif ($this->counter == 1) {
      // simply release the lock
      $this->counter = 0;
      $this->lock->release();
      self::$_acquired_lock = NULL;

    }
    else {
      // lock has already been released!
      Civi::log()->debug(
        "de.systopia.donrec: This process cannot realease lock '$this->name', it has already been released before."
      );
      throw new Exception("This process cannot realease lock '$this->name', it has already been released before.");
    }
  }

}
