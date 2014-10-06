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
 * Exporter for ZIPPED PDF files
 */
class CRM_Donrec_Exporters_PDF extends CRM_Donrec_Exporters_BasePDF {

  /**
   * @return the display name
   */
  static function name() {
    return ts('Actually export individual PDFs');
  }

  /**
   * @return a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '<br/><i>TEST</i>';
  }
}