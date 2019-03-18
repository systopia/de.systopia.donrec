<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

class CRM_Donrec_Logic_WatermarkPreset_SvgAcrossLarge extends CRM_Donrec_Logic_WatermarkPreset {

  public static function getName() {
    return 'SvgAcrossLarge';
  }

  public static function getLabel() {
    return ts('SVG across large', array('domain' => 'de.systopia.donrec'));
  }

  public function injectMarkup(&$html, $pdf_format) {
    return TRUE;
  }

  public function injectStyles(&$html, $pdf_format) {
    $paper_size = CRM_Core_BAO_PaperSize::getByName($pdf_format['paper_size']);
    $paper_width = CRM_Utils_PDF_Utils::convertMetric($paper_size['width'], $paper_size['metric'], $pdf_format['metric']);
    $paper_width -= $pdf_format['margin_left'];
    $paper_width -= $pdf_format['margin_right'];
    $paper_height = CRM_Utils_PDF_Utils::convertMetric($paper_size['height'], $paper_size['metric'], $pdf_format['metric']);
    $paper_height -= $pdf_format['margin_top'];
    $paper_height -= $pdf_format['margin_bottom'];
    $watermark_css = '<style>
                        {if $watermark}
                          {literal}
                          body {
                            background: url("data:image/svg+xml;utf8,\
                            <svg xmlns=\'http://www.w3.org/2000/svg\' version=\'1.1\' height=\'' . $paper_height . $pdf_format['metric'] . '\' width=\'' . $paper_width . $pdf_format['metric'] . '\'>\
                              <text \
                                x=\'33%\'\
                                y=\'66%\'\
                                dx=\'-50%\'\
                                text-anchor=\'middle\'\
                                fill=\'#808080\'\
                                fill-opacity=\'0.2\'\
                                font-size=\'100pt\'\
                                font-family=\'Arial\'\
                                transform=\'rotate(-45)\'\
                              >{/literal}{$watermark}{literal}</text>\
                            </svg>");
                            background-repeat: repeat;
                            width: ' . $paper_width . $pdf_format['metric'] . ';
                            height: ' . $paper_height . $pdf_format['metric'] . ';
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
