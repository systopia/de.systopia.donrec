<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

declare(strict_types = 1);

use CRM_Donrec_ExtensionUtil as E;

/**
 * Exporter for ZIPPED PDF files
 */
class CRM_Donrec_Exporters_PDF extends CRM_Donrec_Exporters_EncryptedPDF {

  /**
   * @return string
   *   the display name
   */
  public static function name() {
    return E::ts('Individual PDFs');
  }

  /**
   * @return string
   *   a html snippet that defines the options as form elements
   */
  public static function htmlOptions() {
    return '';
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
    // encrypt PDF if configured in profile.
    $this->encrypt_file($file, $snapshot_receipt);

    $snapshot_line_id = $snapshot_receipt->getID();
    $this->updateProcessInformation($snapshot_line_id, ['pdf_file' => $file]);
    return TRUE;
  }

  /**
   * @inheritDoc
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   */
  public function wrapUp($snapshot_id, $is_test, $is_bulk) {
  // phpcs:enable
    $reply = [];

    // create the zip file
    $config = CRM_Core_Config::singleton();

    $pdf_count = 0;
    $last_pdf_file = NULL;
    $archiveFileName = CRM_Donrec_Logic_File::makeFileName(E::ts('donation_receipts'), '.zip');
    $zip = new ZipArchive();
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    $toRemove = [];

    if ($zip->open($archiveFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
      foreach ($ids as $id) {
        $proc_info = $snapshot->getProcessInformation($id);
        if (!empty($proc_info)) {
          $filename = isset($proc_info['PDF']['pdf_file']) ? $proc_info['PDF']['pdf_file'] : FALSE;
          if ($filename) {
            $last_pdf_file = $filename;
            $pdf_count += 1;
            $toRemove[$id] = $filename;
            $opResult = $zip->addFile($filename, basename($filename));
            CRM_Donrec_Logic_Exporter::addLogEntry(
              $reply,
              "adding <span title='$filename'>created PDF file</span> to "
                . "<span title='$archiveFileName'>ZIP archive</span> ($opResult)",
              CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG
            );
          }
        }
      }
      if (!$zip->close()) {
        CRM_Donrec_Logic_Exporter::addLogEntry(
          $reply,
          'zip->close() returned false!',
          CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR
        );
      }
    }
    else {
      CRM_Donrec_Logic_Exporter::addLogEntry(
        $reply,
        sprintf('PDF processing failed: Could not open zip file '),
        CRM_Donrec_Logic_Exporter::LOG_TYPE_FATAL
      );
      return $reply;
    }

    if ($pdf_count == 1) {
      $file = CRM_Donrec_Logic_File::createTemporaryFile($last_pdf_file, basename($last_pdf_file));
      Civi::log()->debug("de.systopia.donrec: resulting PDF file URL is '$file'.");
      if (!empty($file)) {
        $reply['download_name'] = basename($last_pdf_file);
        $reply['download_url']  = $file;
      }
    }
    else {
      $preferredFileName = E::ts('donation_receipts.zip');
      $file = CRM_Donrec_Logic_File::createTemporaryFile($archiveFileName, $preferredFileName);
      Civi::log()->debug("de.systopia.donrec: resulting ZIP file URL is '$file'.");
      if (!empty($file)) {
        $reply['download_name'] = $preferredFileName;
        $reply['download_url']  = $file;
      }
    }

    // remove loose pdf files or store them
    CRM_Donrec_Logic_Exporter::addLogEntry(
      $reply,
      'Removing temporary PDF files.',
      CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG
    );
    foreach ($toRemove as $file) {
      unlink($file);
    }

    CRM_Donrec_Logic_Exporter::addLogEntry(
      $reply,
      'PDF generation process ended.',
      CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO
    );
    return $reply;
  }

}
