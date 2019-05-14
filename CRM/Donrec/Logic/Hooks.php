<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class represents the engine for donation receipt runs
 */
class CRM_Donrec_Logic_Hooks {

  /**
   * This hook allows adding additional tokens to the donation receipt.
   *
   * CAUTION: These values are not necessarily stored in the receipt, resulting
   *   in a receipt copy potentially being different from the original
   *
   * @param array  $values  all the currently available tokens - to be extended by you!
   *
   * @access public
   * @return mixed
   */
  static function donationReceiptTokenValues(&$values) {
    if (version_compare(CRM_Utils_System::version(), '4.5', '<'))
    {
      return CRM_Utils_Hook::singleton()->invoke(3, $values, self::$null, self::$null, self::$null, self::$null, 'civicrm_donationReceiptTokenValues');
    }else{
      return CRM_Utils_Hook::singleton()->invoke(3, $values, self::$null, self::$null, self::$null, self::$null, self::$null, 'civicrm_donationReceiptTokenValues');
    }
  }

}
