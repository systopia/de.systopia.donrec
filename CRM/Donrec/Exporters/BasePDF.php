<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This is the PDF exporter base class
 */
class CRM_Donrec_Exporters_BasePDF extends CRM_Donrec_Logic_Exporter {

  public function exportSingle($chunk, $snapshotId, $is_test) {
    $reply = array();

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
        $this->updateProcessInformation($snapshotReceipt->getID(), array('pdf_file' => $result));
        $success++;
      }
    }

    // add a log entry
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processed %d items - %d succeeded, %d failed', count($chunk), $success, $failures), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  public function exportBulk($chunk, $snapshotId, $is_test) {
    $reply = array();

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
        // save file names for wrapup()
        $individualIDs = $snapshotReceipt->getIDs();
        foreach ($individualIDs as $line_id) {
          $this->updateProcessInformation($line_id, array('pdf_file' => $result));
        }
        $success++;
      }
    }
    // add a log entry
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processed %d items - %d succeeded', count($chunk), $success, $failures), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * generate the final result
   *
   * @return array:
   *          'is_error': set if there is a fatal error
   *          'log': array with keys: 'type', 'level', 'timestamp', 'message'
   *          'download_url: URL to download the result
   *          'download_name: suggested file name for the download
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
    $reply = array();

    // create the zip file
    $config = CRM_Core_Config::singleton();

    $preferredFileName = ts("donation_receipts.zip");
    $archiveFileName = CRM_Donrec_Logic_File::makeFileName(ts("donation_receipts"), ".zip");
    $zip = new ZipArchive();
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    $toRemove = array();

    if ($zip->open($archiveFileName, ZIPARCHIVE::CREATE) === TRUE) {
      foreach($ids as $id) {
        $proc_info = $snapshot->getProcessInformation($id);
        if(!empty($proc_info)) {
          $filename = isset($proc_info['PDF']['pdf_file']) ? $proc_info['PDF']['pdf_file'] : FALSE;
          if ($filename) {
            $toRemove[$id] = $filename;
            $opResult = $zip->addFile($filename, basename($filename)) ;
            CRM_Donrec_Logic_Exporter::addLogEntry($reply, "trying to add $filename to archive $archiveFileName ($opResult)", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
          }
        }
      }
      if(!$zip->close()) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'zip->close() returned false!', CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
      }
    }else{
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Could not open zip file '), CRM_Donrec_Logic_Exporter::LOG_TYPE_FATAL);
      return $reply;
    }

    $file = CRM_Donrec_Logic_File::createTemporaryFile($archiveFileName, $preferredFileName);
    if (!empty($file)) {
      $reply['download_name'] = $preferredFileName;
      $reply['download_url'] = $file;
    }

    // remove loose pdf files or store them
    if(!CRM_Donrec_Logic_Settings::saveOriginalPDF()) {
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Removing loose pdf files.', CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
      foreach($toRemove as $file) {
        unlink($file);
      }
    }

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'PDF generation process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

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
}
