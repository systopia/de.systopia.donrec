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

  public function exportSingle($chunk) {
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

      $tpl_param = array();
      $result = $template->generatePDF($values, $tpl_param);
      // TODO: Make the file downloadable
      if ($result === FALSE) {
        $failures++;
      }else{
        $success++;
      }
    }

    // add a log entry
    CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processed %d items - %d succeeded, %d failed', count($chunk), $success, $failures), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
    return $reply;
  }

  public function exportBulk($chunk) {
    
  }

  public function wrapUp($chunk) {
    
  }

  /**
   * @return the ID of this importer class
   */
  public function getID() {
    return 'PDF';
  }
}