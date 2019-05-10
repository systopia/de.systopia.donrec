<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: Luciano Spiegel                                |
|         Björn Endres                                   |
| http://www.ixiam.com/                                  |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class holds helper functions for language
 * related functions
 *
 * Thanks to Luciano from IXIAM for the first implementation
 */
abstract class CRM_Donrec_Lang {

  protected static $locale2language = [];

  /**
   * Get a list of available languages
   *
   * @return array locale => language name
   */
  public static function getLanguageList() {
    // TODO: scan the include path for 'CRM/Donrec/XX/XX' files
    $locale_list = ['en_US', 'de_DE', 'de_XX', 'es_ES', 'pl_PL'];

    $language_list = [];
    foreach ($locale_list as $locale) {
      $lang = self::getLanguage($locale);
      if ($lang) {
        $language_list[$locale] = $lang->getName();
      }
    }
    return $language_list;
  }

  /**
   * Get an instance of the language, or a fallback if it doesn't exist
   *
   * @param $locale string locale
   * @return  CRM_Donrec_Lang|null
   */
  public static function getLanguage($locale = NULL) {
    if (!$locale) {
      // read the setting
      $locale = CRM_Donrec_Logic_Settings::get('donrec_language');
    }

    if (!$locale) {
      // fallback: get the current user's locale
      if (method_exists('CRM_Core_I18n', 'getLocale')) {
        $locale = CRM_Core_I18n::getLocale();
      }
    }

    if (empty($locale)) {
      // final fallback: English
      $locale = 'en_US';
    }

    if (!array_key_exists($locale, self::$locale2language)) {
      // create an instance
      $language = self::_getLanguage($locale);
      if (!$language) {
        // locale not found. Try to find a similar one
        $locale_lang = substr($locale, 0, 2);
        $all_locales = CRM_Core_I18n::languages(FALSE);
        foreach ($all_locales as $possible_locale => $possible_locale_name) {
          if ($locale_lang == substr($possible_locale, 0, 2)) {
            // this is the same language
            $language = self::_getLanguage(substr($possible_locale, 0, 5));
            if ($language) {
              break;
            }
          }
        }

        if (!$language) {
          // fallback is EN_US
          $language = self::_getLanguage('en_US');
        }
      }

      self::$locale2language[$locale] = $language;
    }
    return self::$locale2language[$locale];
  }

  /**
   * Get an instance of the language class if it exists
   *
   * @param $locale string locale
   * @return  CRM_Utils_DonrecLang|null
   */
  protected static function _getLanguage($locale) {
    $locale = substr($locale, 0, 5);

    // remark: dont' want to use ucwords with delimiter, since some people still use PHP 5.4 (I know...)
    $locale_parts = explode('_', strtolower($locale));
    $class = 'CRM_Donrec_Lang_' . ucwords($locale_parts[0]) . '_' . ucwords($locale_parts[1]);
    if (class_exists($class)) {
      return new $class();
    } else {
      CRM_Core_Error::debug_log_message("Unable to find class for lang {$locale}");
      return null;
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
    // fallback: return currency symbol
    return $currency;
  }


  /**
   * Render a full text expressing the amount in the given currency
   *
   * @param $amount   string amount
   * @param $currency string currency. Leave empty to render without currency
   * @param $params   array additional parameters
   * @return string rendered string in the given language
   */
  public abstract function amount2words($amount, $currency, $params = []);

  /**
   * Get the (localised) name of the language
   *
   * @return string name of the language
   */
  public abstract function getName();

}
