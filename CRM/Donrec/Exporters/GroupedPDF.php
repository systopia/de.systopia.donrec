<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

//FIXME: implement own getID-method
/**
 * Exporter for GROUPED, ZIPPED PDF files
 */
class CRM_Donrec_Exporters_GroupedPDF extends CRM_Donrec_Exporters_EncryptedPDF {

  /**
   * @return string
   *   the display name
   */
  static function name() {
    return E::ts('Individual PDFs sorted by page count');
  }

  /**
   * @return string
   *   a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '';
  }

  /**
   * check whether all requirements are met to run this exporter
   *
   * @return array:
   *         'is_error': set if there is a fatal error
   *         'message': error message
   */
  public function checkRequirements($profile = NULL): array {
    $result = array();

    $result['is_error'] = FALSE;
    $result['message'] = '';

    /*
      check if xpdf pdfinfo is available
    */
    $pdfinfo_path = CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path');
    if(!empty($pdfinfo_path)) {
        // "ping" pdfinfo
        $cmd = escapeshellcmd($pdfinfo_path . ' -v') . ' 2>&1';
        exec($cmd, $output, $ret_status);

        // check version
        if (!empty($output) && preg_match('/pdfinfo version ([0-9]+\.[0-9]+\.[0-9]+)/', $output[0], $matches)) {
          $pdfinfo_version = $matches[1];
          if(!empty($matches) && count($matches) == 2) {
            if (version_compare($pdfinfo_version, '0.18.4') >= 0) {
              $result['message'] = sprintf(E::ts("using pdfinfo %s"), $pdfinfo_version);
            }else{
              $result['is_error'] = TRUE;
              $result['message'] = sprintf(E::ts("pdfinfo %s is not supported"), $pdfinfo_version);
            }
          }else{
            $result['is_error'] = TRUE;
            $result['message'] = E::ts("unknown pdfinfo version");
          }
        }else{
          $result['is_error'] = TRUE;
          if($ret_status == 126) { //  126 - Permission problem or command is not an executable
            $result['message'] = E::ts("pdfinfo is not executable. check permissions");
          }else{
            $result['message'] = E::ts("pdfinfo not found");
          }
        }
    }else{
        $result['is_error'] = TRUE;
        $result['message'] = E::ts("pdfinfo path is not set");
    }
    if($result['is_error'] == FALSE) {
      $msg = $result['message'];
      $result = parent::checkRequirements($profile);
      if($result['is_error'] == FALSE){
        $result['message'] = $msg;
      }
    }
    return $result;
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
    $snapshot_line_id = $snapshot_receipt->getID();
    $pageCount = $this->getPDFPageCount($file);

    // encrypt PDF if configured in profile.
    $this->encrypt_file($file, $snapshot_receipt);

    $this->updateProcessInformation($snapshot_line_id,
      array( 'pdf_file'      => $file,
             'pdf_pagecount' => $pageCount));

    return TRUE;
  }

  /**
   * generate the final result
   *
   * @param int $snapshot_id
   * @param bool $is_test
   * @param bool $is_bulk
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

    $preferredFileName = E::ts("donation_receipts");
    $preferredSuffix = E::ts('.zip');
    $archiveFileName = CRM_Donrec_Logic_File::makeFileName($preferredFileName, $preferredSuffix);
    $fileURL = $archiveFileName;
    $outerArchive = new ZipArchive;
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    $toRemove = array();

    // Sort array by page count
    $pageCountArr = array();
    foreach($ids as $id) {
      $proc_info = $snapshot->getProcessInformation($id);
      if(!empty($proc_info)) {
        $pageCount = isset($proc_info['PDF']['pdf_pagecount']) ? $proc_info['PDF']['pdf_pagecount'] : FALSE;
        $filename = isset($proc_info['PDF']['pdf_file']) ? $proc_info['PDF']['pdf_file'] : FALSE;
        if ($pageCount) {
          $pageCountArr[$pageCount][] = array($pageCount, $id, $filename);
        }
      }
    }

    // add files to sub-archives
    // open main archive and add sub-archives
    if ($outerArchive->open($fileURL, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === TRUE) {
      foreach($pageCountArr as $entry) {
        foreach ($entry as $item) {
          if($item[0] && $item[2]) { // if page count and file name exists
            $folder = sprintf(E::ts('%d-page'), $item[0]).DIRECTORY_SEPARATOR;
            $opResult = $outerArchive->addFile($item[2], $folder.basename($item[2])) ;
            CRM_Donrec_Logic_Exporter::addLogEntry($reply, "adding <span title='{$item[2]}'>created {$item[0]}-page PDF file</span> ($opResult)", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
          }
        }
      }
      if(!$outerArchive->close()) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'zip->close() returned false!', CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
      }
    } else{
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Could not open zip file '), CRM_Donrec_Logic_Exporter::LOG_TYPE_FATAL);
      return $reply;
    }

    $file = CRM_Donrec_Logic_File::createTemporaryFile($fileURL, $preferredFileName . $preferredSuffix);
    CRM_Core_Error::debug_log_message("de.systopia.donrec: resulting ZIP file URL is '$file'.");
    if (!empty($file)) {
      $reply['download_name'] = $preferredFileName . $preferredSuffix;
      $reply['download_url'] = $file;
    }

    // remove loose pdf files or store them
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Removing temporary files.', CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
    foreach($toRemove as $file) {
      unlink($file);
    }

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'PDF generation process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * get page count for a pdf file
   *
   * @param string $document
   *
   * @return int page count (-1 if there is an error)
   * @throws \CRM_Core_Exception
   */
  private function getPDFPageCount($document)
  {
    $pdfinfo_path = CRM_Donrec_Logic_Settings::get('donrec_pdfinfo_path');
    $cmd = escapeshellarg($pdfinfo_path);
    $document = escapeshellarg($document);
    $cmd = escapeshellcmd("$cmd $document") . " 2>&1";
    exec($cmd, $output);

    $count = 0;
    foreach($output as $line)
    {
      // Extract the number
      if(preg_match("/Pages:\s*(\d+)/i", $line, $matches) === 1)
      {
          return intval($matches[1]);
      }
    }

    return -1;
  }

}
