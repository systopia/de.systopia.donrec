<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
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
   * @return the ID of this importer class
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
  public function checkRequirements() {
    $result = array();

    $result['is_error'] = FALSE;
    $result['message'] = '';

    return $result;
  }


  /**
   * export this chunk of individual items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  public function exportSingle($chunk, $snapshotId, $is_test) {

    // get the default template
    $template = CRM_Donrec_Logic_Template::getDefaultTemplate();

    // get snapshot data
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshotId);
    $snapshotReceipts = $snapshot->getSnapshotReceipts($chunk, false, $is_test);

    $success = 0;
    $failures = 0;
    foreach ($snapshotReceipts as $snapshotReceipt) {
      // get tokens and generate PDF
      $tpl_param = array();
      $values = $snapshotReceipt->getAllTokens();
      $result = $template->generatePDF($values, $tpl_param);
      if ($result === FALSE) {
        $failures++;
      } else {
        // save file names for wrapup()
        $this->postprocessPDF($result, $snapshotReceipt->getID());
        $success++;
      }
    }

    return boolval($success);
  }

  /**
   * bulk-export this chunk of items
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   */
  public function exportBulk($chunk, $snapshotId, $is_test) {

    // get the default template
    $template = CRM_Donrec_Logic_Template::getDefaultTemplate();

    // get snapshot data
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshotId);
    $snapshotReceipts = $snapshot->getSnapshotReceipts($chunk, true, $is_test);


    $success = 0;
    $failures = 0;
    foreach ($snapshotReceipts as $snapshotReceipt) {
      // get tokens and generate PDF
      $tpl_param = array();
      $values = $snapshotReceipt->getAllTokens();
      $result = $template->generatePDF($values, $tpl_param);
      if ($result === FALSE) {
        $failures++;
      } else {
        // fix: only postprocess once(!)
        $this->postprocessPDF($result, $snapshotReceipt->getID());
        $success++;
      }
    }

    return boolval($success);
  }

  /**
   * allows the subclasses to process the newly created PDF file
   */
  protected function postprocessPDF($file, $snapshot_line_id) {}

}
