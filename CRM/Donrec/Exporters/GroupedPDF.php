<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N. Bochan (bochan -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * Exporter for GROUPED, ZIPPED PDF files
 */
class CRM_Donrec_Exporters_GroupedPDF extends CRM_Donrec_Exporters_BasePDF {

  /**
   * @return the display name
   */
  static function name() {
    return ts('Individual PDFs sorted by page count');
  }

  /**
   * @return a html snippet that defines the options as form elements
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
  public function checkRequirements() {
    $result = array();

    $result['is_error'] = FALSE;
    $result['message'] = '';

    /*
      check if xpdf pdfinfo is available
    */
    $pdfinfo_path = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'pdfinfo_path');
    if(!empty($pdfinfo_path)) {
        // "ping" pdfinfo
        $cmd = escapeshellcmd($pdfinfo_path . ' -v') . ' 2>&1';
        exec($cmd, $output, $ret_status);

        // check version
        if (!empty($output) && preg_match('/pdfinfo version ([0-9]+\.[0-9]+\.[0-9]+)/', $output[0], $matches)) {
          $pdfinfo_version = $matches[1];
          if(!empty($matches) && count($matches) == 2) {
            if (version_compare($pdfinfo_version, '0.24.5') >= 0) {
              $result['message'] = "using pdfinfo $pdfinfo_version";
            }else{
              $result['is_error'] = TRUE;
              $result['message'] = "pdfinfo $pdfinfo_version is not supported";
            }
          }else{
            $result['is_error'] = TRUE;
            $result['message'] = "found pdfinfo but could not retrieve version";
          }
        }else{
          $result['is_error'] = TRUE;
          if($ret_status == 126) { //	126 - Permission problem or command is not an executable
            $result['message'] = "pdfinfo is not executable. check permissions";
          }else{
            $result['message'] = "pdfinfo ping failed";
          }
        }
    }else{
        $result['is_error'] = TRUE;
        $result['message'] = 'pdfinfo path is not set';
    }
    return $result;
  }


  public function exportSingle($chunk, $snapshotId, $is_test) {
    $reply = array();
    $values = array();

    // get the default template
    $template = CRM_Donrec_Logic_Template::getDefaultTemplate();

    // get domain
    $domain = CRM_Core_BAO_Domain::getDomain();
    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'id' => $domain->contact_id,
    );
    $contact = civicrm_api('Contact', 'get', $params);

    if ($contact['is_error'] != 0 || $contact['count'] != 1) {
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Invalid contact'), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
      return $reply;
    }
    $contact = $contact['values'][0];

    // assign all shared template variables
    $values['organisation'] = $contact;

    $success = 0;
    $failures = 0;
    foreach ($chunk as $chunk_id => $chunk_item) {
      // prepare unique template variables

      // get contributor
      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'id' => $chunk_item['contribution_id'],
      );
      $contrib = civicrm_api('Contribution', 'get', $params);
      if ($contrib['is_error'] != 0 || $contrib['count'] != 1) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Invalid Contribution'), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
        return $reply;
      }
      $contrib = $contrib['values'][0];

      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'id' => $contrib['contact_id'],
      );
      $contributor_contact = civicrm_api('Contact', 'get', $params);
      if ($contributor_contact['is_error'] != 0 || $contributor_contact['count'] != 1) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Invalid Contact'), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
        return $reply;
      }
      $contributor_contact = $contributor_contact['values'][0];

      // assign all unique template variables
      $values['contributor'] = $contributor_contact;
      $values['total'] = $chunk_item['total_amount'];
      $values['totaltext'] = CRM_Utils_DonrecHelper::convert_number_to_words($chunk_item['total_amount']);
      $values['today'] = date("j.n.Y", time());
      $values['date'] = date("d.m.Y",strtotime($chunk_item['receive_date']));
      if($is_test) {
        $values['watermark'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'draft_text');
      }

      $tpl_param = array();
      $result = $template->generatePDF($values, $tpl_param);
      if ($result === FALSE) {
        $failures++;
      }else{
        // save file names for wrapup()
        // get pdf page count
        $config = CRM_Core_Config::singleton();
        $filePath = $config->customFileUploadDir . $result;
        $pageCount = $this->getPDFPageCount($filePath);
        $this->updateProcessInformation($chunk_item['id'], array('pdf_file' => $result, 'pdf_pagecount' => $pageCount));

        $success++;
      }
    }

    // add a log entry
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processed %d items - %d succeeded, %d failed', count($chunk), $success, $failures), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  public function exportBulk($chunk, $snapshotId, $is_test) {
    $reply = array();
    $values = array();

    // get the default template
    $template = CRM_Donrec_Logic_Template::getDefaultTemplate();

    // get domain
    $domain = CRM_Core_BAO_Domain::getDomain();
    $params = array(
      'version' => 3,
      'q' => 'civicrm/ajax/rest',
      'sequential' => 1,
      'id' => $domain->contact_id,
    );
    $contact = civicrm_api('Contact', 'get', $params);

    if ($contact['is_error'] != 0 || $contact['count'] != 1) {
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Invalid contact'), CRM_Donrec_Logic_Exporter::FATAL);
      return $reply;
    }
    $contact = $contact['values'][0];

    // assign all shared template variables
    $values['organisation'] = $contact;

    $success = 0;
    $failures = 0;
    foreach ($chunk as $contact_chunk_id => $chunk_items) {
      // prepare unique template variables

      // get contributor
      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'id' => $chunk_items[0]['contribution_id'],
      );
      $contrib = civicrm_api('Contribution', 'get', $params);
      if ($contrib['is_error'] != 0 || $contrib['count'] < 1) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Invalid Contribution'), CRM_Donrec_Logic_Exporter::FATAL);
        return $reply;
      }
      $contrib = $contrib['values'][0];

      $params = array(
        'version' => 3,
        'q' => 'civicrm/ajax/rest',
        'sequential' => 1,
        'id' => $contrib['contact_id'],
      );
      $contributor_contact = civicrm_api('Contact', 'get', $params);
      if ($contributor_contact['is_error'] != 0 || $contributor_contact['count'] != 1) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Invalid Contact'), CRM_Donrec_Logic_Exporter::FATAL);
        return $reply;
      }
      $contributor_contact = $contributor_contact['values'][0];


      $total_amount = 0.00;
      foreach ($chunk_items as $lineid => $lineval) {
        $total_amount += $lineval['total_amount'];
      }

    // assign all unique template variables
    $values['contributor'] = $contributor_contact;
    $values['total'] = $total_amount;
    $values['totaltext'] = CRM_Utils_DonrecHelper::convert_number_to_words($total_amount);
    $values['today'] = date("j.n.Y", time());
    $values['items'] = $chunk_items;
    if($is_test) {
      $values['watermark'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'draft_text');
    }

    $tpl_param = array();
    $result = $template->generatePDF($values, $tpl_param);
    if ($result === FALSE) {
      $failures++;
    }else{
      $config = CRM_Core_Config::singleton();
      // save file names for wrapup()
      foreach($chunk_items as $key => $item) {
        // get pdf page count
        $filePath = $config->customFileUploadDir . $result;
        $pageCount = $this->getPDFPageCount($filePath);
        $this->updateProcessInformation($item['id'], array('pdf_file' => $result, 'pdf_pagecount' => $pageCount));
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
    $archiveFileName = CRM_Utils_DonrecHelper::makeFileName($preferredFileName);
    $fileURL = $config->customFileUploadDir . $archiveFileName;
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

    // create and open a zip file for each (page count) group
    $zipPool = array();
    $pageCountArrKeys = array_keys($pageCountArr);
    foreach($pageCountArrKeys as $groupId => $value) {
      $tmp = new ZipArchive;
      $pcPreferredFileName = sprintf(ts('%d-page(s).zip'), $value);
      $pcArchiveFileName = CRM_Utils_DonrecHelper::makeFileName($preferredFileName);
      $pcFileURL = $config->customFileUploadDir . $pcArchiveFileName;

      if ($tmp->open($pcFileURL, ZIPARCHIVE::CREATE) === TRUE) {
        $zipPool[$value] = array('handle' => $tmp, 'file' => $pcArchiveFileName, 'pref_name' => $pcPreferredFileName);
      }else{
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Could not open zip file %s', $fileURL), CRM_Donrec_Logic_Exporter::FATAL);
        return $reply;
      }
    }

    // add files to sub-archives
    foreach($pageCountArr as $entry) {
      foreach ($entry as $item) {
        if($item[0] && $item[2]) { // if page count and file name exists
          $opResult = $zipPool[$item[0]]['handle']->addFile($config->customFileUploadDir . $item[2], basename($item[2])) ;
          CRM_Donrec_Logic_Exporter::addLogEntry($reply, "trying to add " . $item[2] . " to sub-archive $pcArchiveFileName ($opResult)", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
        }
      }
    }

    // close sub-archives
    foreach($zipPool as $archive) {
      if(!$archive['handle']->close()) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'archive->close() returned false for file' . $archive['file'], CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
      }
    }

    // open main archive and add sub-archives
    if ($outerArchive->open($fileURL, ZIPARCHIVE::CREATE) === TRUE) {
      foreach($zipPool as $zip) {
        $filename = $zip['file'];
        if ($filename) {
          $toRemove[] = $config->customFileUploadDir . $filename;
          $opResult = $outerArchive->addFile($config->customFileUploadDir . $filename, $zip['pref_name']) ;
          CRM_Donrec_Logic_Exporter::addLogEntry($reply, "trying to add $filename to archive $archiveFileName ($opResult)", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
        }
      }
      if(!$outerArchive->close()) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'zip->close() returned false!', CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
      }
    }else{
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Could not open zip file '), CRM_Donrec_Logic_Exporter::FATAL);
      return $reply;
    }

    $file = CRM_Donrec_Logic_File::createTemporaryFile($fileURL, $preferredFileName);
    if (!empty($file)) {
      $reply['download_name'] = $preferredFileName;
      $reply['download_url'] = $file;
    }

    // remove loose pdf files or store them
    if(!CRM_Donrec_Logic_Settings::saveOriginalPDF()) {
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'Removing loose files.', CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
      foreach($toRemove as $file) {
        unlink($file);
      }
    }

    CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'PDF generation process ended.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  /**
   * get page count for a pdf file
   *
   * @return int page count (-1 if there is an error)
   */
  private function getPDFPageCount($document)
  {
      $pdfinfo_path = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'pdfinfo_path');
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
