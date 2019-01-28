<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

abstract class CRM_Donrec_Logic_WatermarkPreset {

  /**
   * @return string
   */
  abstract public static function getName();

  /**
   * @return string
   */
  abstract public static function getLabel();

  /**
   * @param string $html
   *
   * @return bool
   */
  abstract public function injectStyles(&$html);

  /**
   * @param string $html
   *
   * @return bool
   */
  abstract public function injectMarkup(&$html);

}
