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
 * This is the PDF exporter base class
 */
abstract class CRM_Donrec_Exporters_BasePDF extends CRM_Donrec_Logic_Exporter {

  /**
   * @return string
   *   the ID of this importer class
   */
  public function getID() {
    return 'PDF';
  }

  /**
   * check whether all requirements are met to run this exporter
   *
   * @return array:
   *         'is_error': set if there is a fatal error
   *         'message': error message
   */
  public function checkRequirements($profile = NULL) {
    $result = array();

    $result['is_error'] = FALSE;
    $result['message'] = '';

    return $result;
  }


  /**
   * export an individual receipt
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   *
   * @param bool $is_test
   *
   * @return TRUE on success; FALSE on failure
   */
  public function exportSingle($snapshot_receipt, $is_test) {
    // get template of profile in use
    $profile = $snapshot_receipt->getProfile();
    $template = $profile->getTemplate();

    // get tokens and generate PDF
    $tpl_param = array();
    $values = $snapshot_receipt->getAllTokens();
    $result = $template->generatePDF($values, $tpl_param, $profile);
    if ($result === FALSE) {
      return FALSE;
    } else {
      // save file names for wrapup()
      return $this->postprocessPDF($result, $snapshot_receipt, $is_test);
    }
  }

  /**
   * export a bulk-receipt
   *
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   *
   * @param bool $is_test
   *
   * @return bool
   *   TRUE on success; FALSE on failure
   */
  public function exportBulk($snapshot_receipt, $is_test) {

    // same logic as exportSingle()
    return $this->exportSingle($snapshot_receipt, $is_test);
  }

  /**
   * allows the subclasses to process the newly created PDF file
   *
   * @param $file
   * @param \CRM_Donrec_Logic_SnapshotReceipt $snapshot_receipt
   * @param bool $is_test
   *
   * @return bool
   */
  protected function postprocessPDF($file, $snapshot_receipt, $is_test) {
    return TRUE;
  }

}
