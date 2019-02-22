<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

class CRM_Donrec_Logic_WatermarkPreset_SvgUpperRightCorner extends CRM_Donrec_Logic_WatermarkPreset {

  public static function getName() {
    return 'svg_upper_right_corner';
  }

  public static function getLabel() {
    return ts('SVG upper right corner', array('domain' => 'de.systopia.donrec'));
  }

  public function injectMarkup(&$html, $paper_size) {
    return TRUE;
  }

  public function injectStyles(&$html, $paper_size) {
    // TODO: Adjust SVG
    $watermark_css = '<style>
                        {if $watermark}
                          {literal}
                          body {
                            background: url("data:image/svg+xml;utf8,\
                            <svg xmlns=\'http://www.w3.org/2000/svg\' version=\'1.1\' height=\'' . $paper_size['height'] . $paper_size['metric'] . '\' width=\'' . $paper_size['width'] . $paper_size['metric'] . '\'>\
                              <text \
                                x=\'95%\'\
                                y=\'5%\'\
                                text-anchor=\'end\'\
                                fill=\'#808080\'\
                                fill-opacity=\'0.2\'\
                                font-size=\'20pt\'\
                                font-family=\'Arial\'\
                              >{/literal}{$watermark}{literal}</text>\
                            </svg>");
                            background-repeat: repeat;
                            width: ' . $paper_size['width'] . $paper_size['metric'] . ';
                            height: ' . $paper_size['height'] . $paper_size['metric'] . ';
                          }
                          {/literal}
                        {/if}
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
