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
   * @param int $pdf_format
   *
   * @return bool
   */
  abstract public function injectStyles(&$html, $pdf_format);

  /**
   * @param string $html
   *
   * @param array $pdf_format
   *
   * @return bool
   */
  abstract public function injectMarkup(&$html, $pdf_format);

  /**
   * Retrieves the default watermark preset name, depending on whether
   * wkhtmltopdf is enabled.
   *
   * @return string
   *   The default watermark's name.
   */
  public static function getDefaultWatermarkPresetName() {
    if (!empty(CRM_Core_Config::singleton()->wkhtmltopdfPath)) {
      $preset_name = CRM_Donrec_Logic_WatermarkPreset_WkhtmltopdfTraditional::getName();
    }
    else {
      $preset_name = CRM_Donrec_Logic_WatermarkPreset_DompdfTraditional::getName();
    }

    return $preset_name;
  }

}
