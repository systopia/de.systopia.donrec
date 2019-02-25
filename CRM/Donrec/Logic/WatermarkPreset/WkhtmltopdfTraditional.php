<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

class CRM_Donrec_Logic_WatermarkPreset_WkhtmltopdfTraditional extends CRM_Donrec_Logic_WatermarkPreset {

  public static function getName() {
    return 'WkhtmltopdfTraditional';
  }

  public static function getLabel() {
    return ts('Markup traditional (wkhtmltopdf)', array('domain' => 'de.systopia.donrec'));
  }

  public function injectMarkup(&$html, $paper_size) {
    $watermark_site = '<div class="watermark watermark-center">{if $watermark}{$watermark}{/if}</div>';

    $matches = array();
    preg_match('/<body[^>]*>/', $html, $matches, PREG_OFFSET_CAPTURE);
    if (count($matches) == 1) {
      $body_offset = $matches[0][1];
      $html = substr_replace($html, $watermark_site, $body_offset + strlen($matches[0][0]), 0);
    }else if (count($matches) < 1) {
      CRM_Core_Error::debug_log_message('de.systopia.donrec: watermark could not be created for site one (<body> not found). pdf rendering cancelled.');
      return FALSE;
    }

    return TRUE;
  }

  public function injectStyles(&$html, $paper_size) {
    $watermark_css = '<style>
                        {literal}
                        .watermark {
                          position: fixed;
                          z-index: 999;
                          color: rgba(128, 128, 128, 0.20);
                          -ms-transform: rotate(-45deg); /* IE 9 */
                          -webkit-transform: rotate(-45deg); /* Chrome, Safari, Opera */
                          transform: rotate(-45deg);
                          font-size: 100pt!important;
                        }

                        .watermark-center {
                          left: 10px;
                          top: 400px;
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
