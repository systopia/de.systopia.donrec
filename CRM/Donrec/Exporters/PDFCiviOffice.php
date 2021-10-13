<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2021 SYSTOPIA                       |
| Authors:                                               |
| - B. Endres (endres -at- systopia.de)                  |
| - J. Schuppe (schuppe -at- systopia.de)                |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * Exporter for ZIPPED PDF files
 */
class CRM_Donrec_Exporters_PDFCiviOffice extends CRM_Donrec_Exporters_BasePDF {

  /**
   * @return string
   *   the display name
   */
  static function name() {
    return E::ts('Individual PDFs with cover letter (CiviOffice)');
  }

  /**
   * @return string
   *   a html snippet that defines the options as form elements
   */
  static function htmlOptions() {
    return '';
  }

  public function checkRequirements($profile = NULL) {
    $result = [];

    if (
      $result['is_error'] =
        !($civioffice_document_uri = CRM_Donrec_Logic_Settings::get('donrec_civioffice_document_uri'))
        || !($civioffice_document_renderer_uri = CRM_Donrec_Logic_Settings::get('donrec_civioffice_document_renderer_uri'))
    ) {
      $result['message'] = E::ts('CiviOffice integration is not configured');
    }
    else {
      $civioffice_config = CRM_Civioffice_Configuration::getConfig();
      $result['message'] = E::ts(
        'using document <em>%1</em> and document renderer <em>%2</em>',
        [
          1 => $civioffice_config->getDocument($civioffice_document_uri)->getName(),
          2 => $civioffice_config->getDocumentRenderer($civioffice_document_renderer_uri)->getName(),
        ]
      );
    }

    $result['message'] .= ' &ndash; ' . E::ts(
      'Configure CiviOffice integration in the <a href="%1">Donation Receipts configuration</a>',
        [1 => CRM_Utils_System::url('civicrm/admin/setting/donrec', ['reset' => 1])]
      );

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
    $files = [$snapshot_line_id . '-' . $snapshot_receipt->getContactID() . '-' . E::ts('donation-receipt') => $file];

    // TODO: Generate cover letter PDF using CiviOffice and add to pdf_files array.
    $civioffice_result = civicrm_api3(
      'CiviOffice',
      'convert',
      [
        'document_uri' => CRM_Donrec_Logic_Settings::get('donrec_civioffice_document_uri'),
        'entity_ids' => [$snapshot_receipt->getContactID()],
        'entity_type' => 'contact',
        'renderer_uri' => CRM_Donrec_Logic_Settings::get('donrec_civioffice_document_renderer_uri'),
        'target_mime_type' => 'application/pdf',
      ]
    );
    $result_store_uri = $civioffice_result['values'][0];
    $result_store = CRM_Civioffice_Configuration::getDocumentStore($result_store_uri);
    foreach ($result_store->getDocuments() as $document) {
      /* @var CRM_Civioffice_Document $document */
      $files[$snapshot_line_id . '-' . $snapshot_receipt->getContactID() . '-' . E::ts('cover-letter')] = $document->getLocalTempCopy();
    }

    $this->updateProcessInformation($snapshot_line_id, array('pdf_files' => $files));
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

    $pdf_count = 0;
    $last_pdf_file = NULL;
    $archiveFileName = CRM_Donrec_Logic_File::makeFileName(E::ts("donation_receipts"), ".zip");
    $zip = new ZipArchive();
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    $toRemove = array();

    if ($zip->open($archiveFileName, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === TRUE) {
      foreach($ids as $id) {
        $proc_info = $snapshot->getProcessInformation($id);
        if (!empty($proc_info['PDF']['pdf_files'])) {
          foreach ($proc_info['PDF']['pdf_files'] as $key => $filename) {
            $last_pdf_file = $filename;
            $pdf_count += 1;
            $toRemove[$id] = $filename;
            $pathinfo = pathinfo($filename);
            $opResult = $zip->addFile($filename, $key . '.' . $pathinfo['extension']);
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

    if ($pdf_count == 1) {
      $file = CRM_Donrec_Logic_File::createTemporaryFile($last_pdf_file, basename($last_pdf_file));
      CRM_Core_Error::debug_log_message("de.systopia.donrec: resulting PDF file URL is '$file'.");
      if (!empty($file)) {
        $reply['download_name'] = basename($last_pdf_file);
        $reply['download_url']  = $file;
      }
    } else {
      $preferredFileName = E::ts("donation_receipts.zip");
      $file = CRM_Donrec_Logic_File::createTemporaryFile($archiveFileName, $preferredFileName);
      CRM_Core_Error::debug_log_message("de.systopia.donrec: resulting ZIP file URL is '$file'.");
      if (!empty($file)) {
        $reply['download_name'] = $preferredFileName;
        $reply['download_url']  = $file;
      }
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