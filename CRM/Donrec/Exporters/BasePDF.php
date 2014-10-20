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
        $values['watermark'] = CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'draft_text');;
      }

      $tpl_param = array();
      $result = $template->generatePDF($values, $tpl_param);
      if ($result === FALSE) {
        $failures++;
      }else{
        // save file names for wrapup()
        $snapshot->setProcessInformation($chunk_item['id'], $result);
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
        'id' => $chunk_item[0]['contribution_id'],
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
      // save file names for wrapup()
      foreach($chunk_items as $key => $item) {
        $snapshot->setProcessInformation($item['id'], $result);
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

    $archiveFileName = CRM_Utils_DonrecHelper::makeFileName("donrec.zip");
    $fileURL = $config->customFileUploadDir . $archiveFileName;
    $zip = new ZipArchive;
    $snapshot = CRM_Donrec_Logic_Snapshot::get($snapshot_id);
    $ids = $snapshot->getIds();
    $toRemove = array();

    if ($zip->open($fileURL, ZIPARCHIVE::CREATE) === TRUE) {
      foreach($ids as $id) {
        $filename = $snapshot->getProcessInformation($id);
        if(!empty($filename)) {
          $toRemove[$id] = $filename;
          $opResult = $zip->addFile($filename, basename($filename)) ;
          CRM_Donrec_Logic_Exporter::addLogEntry($reply, "trying to add $filename to archive $archiveFileName ($opResult)", CRM_Donrec_Logic_Exporter::LOG_TYPE_DEBUG);
        }
      }
      if(!$zip->close()) {
        CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'zip->close() returned false!', CRM_Donrec_Logic_Exporter::LOG_TYPE_ERROR);
      }
    }else{
      CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processing failed: Could not open zip file '), CRM_Donrec_Logic_Exporter::FATAL);
      return $reply;
    }


    $file = $this->createFile($archiveFileName);
    if (!empty($file)) {
      $reply['download_name'] = $file[0];
      $reply['download_url'] = $file[1];
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
}
