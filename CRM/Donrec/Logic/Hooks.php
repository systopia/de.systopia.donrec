<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

/**
 * This class represents the engine for donation receipt runs
 */
class CRM_Donrec_Logic_Hooks {

  private static $null = NULL;

  /**
   * This hook allows adding additional tokens to the donation receipt.
   *
   * CAUTION: These values are not necessarily stored in the receipt, resulting
   *   in a receipt copy potentially being different from the original
   *
   * @param array $values
   * @param-out array $values
   *
   * @access public
   * @return mixed
   */
  public static function donationReceiptTokenValues(&$values) {
    return CRM_Utils_Hook::singleton()->invoke(
      ['values'],
      // @phpstan-ignore paramOut.type
      $values,
      self::$null,
      self::$null,
      self::$null,
      self::$null,
      self::$null,
      $hook = 'civicrm_donationReceiptTokenValues'
    );
  }

}
