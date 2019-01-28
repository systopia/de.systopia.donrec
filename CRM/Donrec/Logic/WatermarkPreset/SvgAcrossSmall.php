<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

class CRM_Donrec_Logic_WatermarkPreset_SvgAcrossSmall extends CRM_Donrec_Logic_WatermarkPreset {

  public static function getName() {
    return 'svg_across_small';
  }

  public static function getLabel() {
    return ts('SVG across small', array('domain' => 'de.systopia.donrec'));
  }

  public function injectMarkup(&$html) {
    return TRUE;
  }

  public function injectStyles(&$html) {
    // TODO: Make the SVG smaller.
    $watermark_css = '<style>
                        {literal}
                        body {
                          background: url("data:image/svg+xml;utf8,\
                          <svg xmlns=\'http://www.w3.org/2000/svg\' version=\'1.1\' height=\'29.7cm\' width=\'21cm\'>\
                            <text \
                              x=\'-55%\'\
                              y=\'65%\'\
                              fill=\'#808080\'\
                              fill-opacity=\'0.2\'\
                              font-size=\'100pt\'\
                              font-family=\'Arial\'\
                              transform=\'rotate(-45)\'\
                            >{/literal}{if $watermark}{$watermark}{/if}{literal}</text>\
                          </svg>");
                          background-repeat: repeat;
                        }
                        {/literal}
                        </style>
                        ';

    $matches = array();
    preg_match('/<\/style>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $head_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_css, $head_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: watermark css could not be created (</style> not found). falling back to <body>.');
      $matches = array();
      preg_match('/<body>/', $html, $matches, PREG_OFFSET_CAPTURE);
      if (count($matches) == 1) {
        $head_offset = $matches[0][1];
        $html = substr_replace($html, $watermark_css, $head_offset, 0);
      }else{
        CRM_Core_Error::debug_log_message('de.systopia.donrec: watermark could not be created. pdf rendering cancelled.');
        return FALSE;
      }
    }

    return TRUE;
  }

}
