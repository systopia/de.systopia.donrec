<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: N. Bochan                                      |
|         B. Endres                                      |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * This class holds German language helper functions
  * @param number any number that should be converted to words
  * @author Karl Rixon (http://www.karlrixon.co.uk/writing/convert-numbers-to-words-with-php/)
  *         modified by Niko Bochan to support the German language
  */
class CRM_Donrec_Lang_De_Xx extends CRM_Donrec_Lang {

  /**
   * Get the (localised) name of the language
   *
   * @return string name of the language
   */
  public function getName() {
    return E::ts("German (with spaces)");
  }

  /**
   * Render a full text expressing the amount in the given currency
   *
   *
   * @param $amount   string amount
   * @param $currency string currency. Leave empty to render without currency
   * @param $params   array additional parameters
   * @return string rendered string in the given language
   *
   * @author Karl Rixon (http://www.karlrixon.co.uk/writing/convert-numbers-to-words-with-php/)
   *         modified by Niko Bochan to support the German language
   *         refactored by Björn Endres for DONREC-50
   */
  public function amount2words($amount, $currency, $params = []) {
    return $this->convert_number_to_words($amount, $currency, FALSE);
  }

  /**
   * Get a spoken word representation for the given currency
   *
   * @param $currency string currency symbol, e.g 'EUR' or 'USD'
   * @param $quantity int count, e.g. for plural
   * @return string   spoken word, e.g. 'Euro' or 'Dollar'
   */
  public function currency2word($currency, $quantity) {
    switch ($currency) {
      case 'EUR':
        return 'Euro';
      case 'USD':
        return 'Dollar';
      case 'GBP':
        return 'Britische Pfund';
      case 'CHF':
        return 'Schweizer Franken';
      default:
        return parent::currency2word($currency, $quantity);
    }
  }


  /**
   * Internal (legacy) function
   *
   * @param $number    string any number that should be converted to words
   * @param $currency  string currency
   * @param $recursion bool   recursion protection
   * @author Karl Rixon (http://www.karlrixon.co.uk/writing/convert-numbers-to-words-with-php/)
   *         modified by Niko Bochan to support the German language
   *         refactored by Björn Endres for DONREC-50
   * @return string language
   */
  protected function convert_number_to_words($number, $currency='EUR', $recursion=false) {
    $hyphen      = 'und';
    $conjunction = ' ';
    $separator   = ' ';
    $negative    = 'minus ';
    $decimal     = ' ' . $this->currency2word($currency, $number) . ' ';
    $dictionary  = array(
        0                   => 'null',
        1                   => 'ein',
        2                   => 'zwei',
        3                   => 'drei',
        4                   => 'vier',
        5                   => 'fünf',
        6                   => 'sechs',
        7                   => 'sieben',
        8                   => 'acht',
        9                   => 'neun',
        10                  => 'zehn',
        11                  => 'elf',
        12                  => 'zwölf',
        13                  => 'dreizehn',
        14                  => 'vierzehn',
        15                  => 'fünfzehn',
        16                  => 'sechzehn',
        17                  => 'siebzehn',
        18                  => 'achtzehn',
        19                  => 'neunzehn',
        20                  => 'zwanzig',
        30                  => 'dreißig',
        40                  => 'vierzig',
        50                  => 'fünfzig',
        60                  => 'sechzig',
        70                  => 'siebzig',
        80                  => 'achtzig',
        90                  => 'neunzig',
        100                 => 'hundert',
        1000                => 'tausend',
        1000000             => 'millionen',
        1000000000          => 'milliarden',
        1000000000000       => 'billionen'
    );

    if (!is_numeric($number)) {
      return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
      // overflow
      return false;
    }

    if ($number < 0) {
      return $negative . $this->convert_number_to_words(abs($number), $currency, true);
    }

    $string = null;
    // make sure, the values are set correctly (#1582)
    $fraction = (int) round( ($number - floor($number)) * 100);
    $number = (int) $number;

    switch (true) {
      case $number < 21:
        $string = $dictionary[$number];
        break;
      case $number < 100:
        $tens   = ((int) ($number / 10)) * 10;
        $units  = $number % 10;
        if ($units) {
          $string = $dictionary[$units] . $hyphen . $dictionary[$tens];
        }else{
          $string = $dictionary[$tens];
        }
        break;
      case $number < 1000:
        $hundreds  = $number / 100;
        $remainder = $number % 100;
        $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
        if ($remainder) {
          $string .= $conjunction . $this->convert_number_to_words($remainder, $currency, true);
        }
        break;
      default:
        $baseUnit = pow(1000, floor(log($number, 1000)));
        $numBaseUnits = (int) ($number / $baseUnit);
        $remainder = $number % $baseUnit;
        $string .= $this->convert_number_to_words($numBaseUnits, $currency, true);
        // FIXME: the following doesn't work for units > 10^6
        if ($baseUnit == 1000000 && $numBaseUnits == 1) {
          $string .= 'e ';                                  // ein_e_
          $string .= substr($dictionary[$baseUnit], 0, -2); // million (ohne 'en')
        } else {
          $string .= ' ';
          $string .= $dictionary[$baseUnit];
        }

        if ($remainder) {
          $string .= ($remainder < 100) ? $conjunction : $separator;
          $string .= $this->convert_number_to_words($remainder, $currency, true);
        }
        break;
    }

    if ($fraction) {
      $string .= $decimal;

      if(is_numeric($fraction) && $fraction != 0.00) {
        switch (true) {
          case $fraction < 21:
            $string .= $dictionary[$fraction];
            break;
          case $fraction < 100:
            $tens   = ((int) ($fraction / 10)) * 10;
            $units  = $fraction % 10;
            if ($units) {
              $string .= $dictionary[$units] . $hyphen . $dictionary[$tens];
            }else{
              $string .= $dictionary[$tens];
            }
            break;
        }
      }
    } elseif (!$recursion) {
      $string .= $decimal;
    }

    return trim($string);
  }
}
