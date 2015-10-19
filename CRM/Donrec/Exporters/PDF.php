<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Exporter for ZIPPED PDF files
 */
class CRM_Donrec_Exporters_PDF extends CRM_Donrec_Exporters_BasePDF {

  /**
   * @return the display name
   */
  static function name() {
    return ts('Individual PDFs');
  }

  /**
   * @return a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '';
  }

  /**
   * allows the subclasses to process the newly created PDF file
   */
  protected function postprocessPDF($file, $snapshot_line_id) {
    $this->updateProcessInformation($snapshot_line_id, array('pdf_file' => $file));
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
            CRM_Donrec_Logic_Exporter::addLogEntry($reply, "adding <span title='$filename'>created PDF file</span> to <span title='$archiveFileName'>ZIP archive</span> ($opResult)", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
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
    error_log("de.systopia.donrec: resulting ZIP file URL is '$file'.");
    if (!empty($file)) {
      $reply['download_name'] = $preferredFileName;
      $reply['download_url'] = $file;
    }

    // remove loose pdf files or store them
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Removing temporary PDF files.', CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
    foreach($toRemove as $file) {
      unlink($file);
    }

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'PDF generation process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }  
}