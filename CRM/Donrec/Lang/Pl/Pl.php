<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: Tomasz "Scardinius" Pietrzkowski               |
| http://www.caltha.pl                                   |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/


use CRM_Donrec_ExtensionUtil as E;

require_once 'Kwota.php';

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
    return E::ts("Polish");
  }

  /**
   * Render a full text expressing the amount in the given currency
   *
   * @param $amount   string amount
   * @param $currency string currency. Leave empty to render without currency
   * @param $params   array additional parameters
   * @return string rendered string in the given language
   */
  public function amount2words($amount, $currency, $params = []) {
    try {
      if (function_exists('bcadd')) {
        $kwota = Kwota::getInstance();
        return $kwota->slownie($amount);
      } else {
        CRM_Core_Error::debug_log_message("Donrec: you need to install Bcmath module to use the polish language");
        return 'ERROR';
      }
    } catch(Exception $ex) {
      CRM_Core_Error::debug_log_message("Donrec: couldn't render text representation ({$amount}|pl_PL): " . $ex->getMessage());
      return 'ERROR';
    }
  }

  /**
   * Get a spoken word representation for the given currency
   *
   * @param $currency string currency symbol, e.g 'EUR' or 'USD'
   * @param $quantity int count, e.g. for plural
   * @return string   spoken word, e.g. 'Euro' or 'Dollar'
   */
  public function currency2word($currency, $quantity) {
    return parent::currency2word($currency, $quantity);
  }
}