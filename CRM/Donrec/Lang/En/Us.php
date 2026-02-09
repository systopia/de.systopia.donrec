<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: Luciano Spiegel                                |
| http://www.ixiam.com/                                  |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds English language helper functions
 */
class CRM_Donrec_Lang_En_Us extends CRM_Donrec_Lang {

  /**
   * Get the (localised) name of the language
   *
   * @return string name of the language
   */
  public function getName() {
    return E::ts('English (U.S.)');
  }

  /**
   * Render a full text expressing the amount in the given currency
   *
   * @inheritDoc
   *
   * @author Luciano Spiegel(?)
   */
  public function amount2words($amount, $currency, $params = []) {
    return (string) self::toWords($amount);
  }

  /**
   * @param string|int|float $num
   *
   * @return string|FALSE
   */
  public static function toWords($num) {
    $num = str_replace([',', ' '], '', trim((string) $num));
    if (!$num) {
      return FALSE;
    }
    $num = (int) $num;
    $words = [];
    $list1 = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
      'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen',
    ];
    $list2 = ['', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred'];
    $list3 = [
      '',
      'thousand',
      'million',
      'billion',
      'trillion',
      'quadrillion',
      'quintillion',
      'sextillion',
      'septillion',
      'octillion',
      'nonillion',
      'decillion',
      'undecillion',
      'duodecillion',
      'tredecillion',
      'quattuordecillion',
      'quindecillion',
      'sexdecillion',
      'septendecillion',
      'octodecillion',
      'novemdecillion',
      'vigintillion',
    ];
    $num_length = strlen((string) $num);
    $levels = (int) (($num_length + 2) / 3);
    $max_length = $levels * 3;
    $num = substr('00' . $num, -$max_length);
    $num_levels = str_split($num, 3);
    $num_levels_count = count($num_levels);
    for ($i = 0; $i < $num_levels_count; $i++) {
      $levels--;
      $hundreds = (int) ((int) $num_levels[$i] / 100);
      $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
      $tens = (int) $num_levels[$i] % 100;
      $singles = '';
      if ($tens < 20) {
        $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
      }
      else {
        $tens = (int) ($tens / 10);
        $tens = ' ' . $list2[$tens] . ' ';
        $singles = (int) $num_levels[$i] % 10;
        $singles = ' ' . $list1[$singles] . ' ';
      }
      $words[] = $hundreds . $tens . $singles
        . (($levels && (int) ($num_levels[$i])) ? ' ' . $list3[$levels] . ' ' : '');
    } //end for loop
    $commas = count($words);
    if ($commas > 1) {
      $commas = $commas - 1;
    }
    return implode(' ', $words);
  }

}
