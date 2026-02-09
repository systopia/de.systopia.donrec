<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: Tomasz "Scardinius" Pietrzkowski               |
| http://www.caltha.pl                                   |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

// phpcs:disable PSR1.Files.SideEffects
require_once __DIR__ . '/Kwota.php';
// phpcs:enable

/**
 * This class holds Polish language helper functions
 */
class CRM_Donrec_Lang_Pl_Pl extends CRM_Donrec_Lang {

  /**
   * Get the (localised) name of the language
   *
   * @return string name of the language
   */
  public function getName() {
    return E::ts('Polish');
  }

  /**
   * @inheritDoc
   */
  public function amount2words($amount, $currency, $params = []) {
    try {
      if (function_exists('bcadd')) {
        $kwota = Kwota::getInstance();
        return $kwota->slownie($amount);
      }
      else {
        Civi::log()->debug('Donrec: you need to install Bcmath module to use the polish language');
        return 'ERROR';
      }
    }
    catch (Exception $ex) {
      // @ignoreException
      Civi::log()->debug("Donrec: couldn't render text representation ({$amount}|pl_PL): " . $ex->getMessage());
      return 'ERROR';
    }
  }

  /**
   * @inheritDoc
   */
  public function currency2word($currency, $quantity) {
    return parent::currency2word($currency, $quantity);
  }

}
