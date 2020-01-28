<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: J. Schuppe (schuppe@systopia.de)               |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

//FIXME: implement own getID-method

/**
 * Exporter for MERGED PDF files.
 */
class CRM_Donrec_Exporters_MergedPDF extends CRM_Donrec_Exporters_BasePDF {

  /**
   * @return string
   *   The display name.
   */
  static function name() {
    return E::ts('PDFs merged into one PDF file');
  }

  /**
   * @return string
   *   An HTML snippet that defines the options as form elements.
   */
  static function htmlOptions() {
    return '';
  }

  /**
   * Checks whether all requirements are met to run this exporter.
   *
   * @return array
   *   An array with the following keys:
   *    - "is_error": Set if there is a fatal error
   *    - "message": Error message
   */
  public function checkRequirements($profile = NULL) {
    $result = array();

    $result['is_error'] = FALSE;
    $result['message'] = '';

    // Check if pdfunite is available.
    $pdfunite_path = CRM_Donrec_Logic_Settings::get('donrec_pdfunite_path');
    if (!empty($pdfunite_path)) {
      // "Ping" pdfunite.
      $cmd = escapeshellcmd($pdfunite_path . ' -v') . ' 2>&1';
      exec($cmd, $output, $ret_status);

      // check pdfunite version.
      if (!empty($output) && preg_match('/pdfunite version ([0-9]+\.[0-9]+\.[0-9]+)/', $output[0], $matches)) {
        $pdfunite_version = $matches[1];
        if (!empty($matches) && count($matches) == 2) {
          $result['message'] = sprintf(E::ts("using pdfunite %s"), $pdfunite_version);
        }
        else {
          $result['is_error'] = TRUE;
          $result['message'] = E::ts("unknown pdfunite version");
        }
      }
      else {
        $result['is_error'] = TRUE;
        if ($ret_status == 126) { //  126 - Permission problem or command is not an executable
          $result['message'] = E::ts("pdfunite is not executable. check permissions");
        }
        else {
          $result['message'] = E::ts("pdfunite not found");
        }
      }
    }
    else {
      $result['is_error'] = TRUE;
      $result['message'] = E::ts("pdfunite path is not set");
    }
    return $result;
  }


  /**
   * Allows the subclasses to process the newly created PDF file.
   */
  protected function postprocessPDF($file, $snapshot_receipt, $is_test) {
    $snapshot_line_id = $snapshot_receipt->getID();
    $this->updateProcessInformation($snapshot_line_id, array('pdf_file' => $file));
    return TRUE;
  }

  /**
   * Generates the final result.
   *
   * @return array
   *   An array with the following keys:
   *     - 'is_error': Set if there is a fatal error
   *     - 'log': An array with the following keys:
   *         - 'type'
   *         - 'level'
   *         - 'timestamp'
   *         - 'message'
   *     - 'download_url: The URL to download the result
   *     - 'download_name: The suggested file name for the download
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
    $reply = array();

    // Create the merged PDF file.
    $config = CRM_Core_Config::singleton();

    $preferredFileName = E::ts("donation_receipts");
    $preferredSuffix = E::ts('.pdf');
    $mergedPDFFileName = CRM_Donrec_Logic_File::makeFileName($preferredFileName, $preferredSuffix);
    $fileURL = $mergedPDFFileName;
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    $toRemove = array();

    // Merge all PDF files using pdfunite.
    foreach ($ids as $id) {
      $proc_info = $snapshot->getProcessInformation($id);
      if (!empty($proc_info)) {
        $filename = isset($proc_info['PDF']['pdf_file']) ? $proc_info['PDF']['pdf_file'] : FALSE;
        if ($filename) {
          $toRemove[$id] = $filename;
          CRM_Donrec_Logic_Exporter::addLogEntry($reply, "adding <span title='$filename'>created PDF file</span> to <span title='$mergedPDFFileName'>merged PDF file</span>", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
        }
      }
    }
    $pdfunite_path = CRM_Donrec_Logic_Settings::get('donrec_pdfunite_path');
    $cmd = escapeshellcmd($pdfunite_path . ' ' . implode(' ', $toRemove)) . ' ' . $fileURL . ' 2>&1';
    exec($cmd, $output, $ret_status);

    $file = CRM_Donrec_Logic_File::createTemporaryFile($fileURL, $preferredFileName . $preferredSuffix);
    CRM_Core_Error::debug_log_message("de.systopia.donrec: resulting PDF file URL is '$file'.");
    if (!empty($file)) {
      $reply['download_name'] = $preferredFileName . $preferredSuffix;
      $reply['download_url'] = $file;
    }

    // Remove loose PDF files or store them.
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Removing temporary files.', CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
    foreach ($toRemove as $file) {
      unlink($file);
    }

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'PDF generation process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

}
