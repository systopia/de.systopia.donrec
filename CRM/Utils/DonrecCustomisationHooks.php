<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
* defines customization hooks
*/
class CRM_Utils_DonrecCustomisationHooks {
  static $null = NULL;

  /**
  * This hook is called once for every chunk item before the pdf template is rendered.
  * You should use this when the token cannot be shared between chunk items (for example a
  * unique document id that is part of the pdf file)
  *
  * You can implement this hook to add/modify template tokens
  * e.g. in your hook implementation call $template->assign('myCustomToken', 'my custom token');
  * and place a token called {$myCustomToken} in the template.
  *
  * @param object $template
   *
   * @param $chunk_item
  *
  * @return mixed based on op. pre-hooks return a boolean or
  * an error message which aborts the operation
  * @access public
  */
  static function pdf_unique_token(&$template, &$chunk_item) {
    if (version_compare(CRM_Utils_System::version(), '4.5', '<'))
    {
      return CRM_Utils_Hook::singleton()->invoke(1, $template, $chunk_item, self::$null, self::$null, self::$null, $hook = 'civicrm_pdf_unique_token');
    } else {
      return CRM_Utils_Hook::singleton()->invoke(1, $template, $chunk_item, self::$null, self::$null, self::$null, self::$null, $hook = 'civicrm_pdf_unique_token');
    }
  }

  /**
   * This hook is called once for every batch before the pdf template is
   * rendered. You should use this for performance reasons when the token can
   * be shared between chunk items (for example a contact address that is the
   * same for all files)
   *
   * You can implement this hook to add/modify template tokens
   * e.g. in your hook implementation call $template->assign('myCustomToken',
   * 'my custom token'); and place a token called {$myCustomToken} in the
   * template.
   *
   * @param object $template
   *
   * @param $chunk
   *
   * @return mixed based on op. pre-hooks return a boolean or
   * an error message which aborts the operation
   * @access public
   *
   * TODO: The $chunk parameter is not used, instead $chunk_item
   */
  static function pdf_shared_token(&$template, &$chunk_item) {
    if (version_compare(CRM_Utils_System::version(), '4.5', '<'))
    {
      return CRM_Utils_Hook::singleton()->invoke(1, $template, $chunk_item, self::$null, self::$null, self::$null, $hook = 'civicrm_pdf_shared_token');
    }else{
      return CRM_Utils_Hook::singleton()->invoke(1, $template, $chunk_item, self::$null, self::$null, self::$null, self::$null, $hook = 'civicrm_pdf_shared_token');
    }
  }
}
