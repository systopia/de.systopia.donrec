<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This class represents a single SnapShot line as a single, temparary receipt
 * or a list of snapshot lines in case of the bulk receipt
 */
class CRM_Donrec_Logic_SnapshotReceipt extends CRM_Donrec_Logic_ReceiptTokens {
    
  protected $snapshot;  
  protected $snapshot_lines;  
  protected $is_test;

  private $cached_contributors = array();
  private $cached_addressees = array();

  public function __construct($snapshot, $snapshot_lines, $is_test) {
    $this->snapshot = $snapshot;
    $this->snapshot_lines = $snapshot_lines;
    $this->is_test = $is_test;
  }

  public function isBulk() {
    return count($this->snapshot_lines) > 1;
  }

  /**
   * gets the line ID in case of a single line receipt
   *
   * @return snapshot line ID if single, NULL if bulk
   */
  public function getID() {
    if ($this->isBulk()) {
      return NULL;
    } else {
      return reset($this->snapshot_lines)['id'];
    }
  }

  /**
   * gets the line IDs in case of a bulk line receipt
   *
   * @return snapshot line IDs if bulk, NULL if single
   */
  public function getIDs() {
    if ($this->isBulk()) {
      foreach ($this->snapshot_lines as $snapshot_line) {
        $line_ids[] = $snapshot_line['id'];
      }
      return $line_ids;
    } else {
      return NULL;
    }
  }

  /**
   * Get all the lines
   */
  public function getLines() {
    foreach ($this->snapshot_lines as $snapshot_line) {
      $lines[$snapshot_line['id']] = $snapshot_line;
    }
    return $lines;
  }

  /**
   * Get all properties of this receipt token source, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * @return array of properties
   */
  public function getAllTokens() {
    $values = array();

    // create items
    $values['status']                = '';
    $values['issued_on']             = strtotime('now');
    $values['total_amount']          = 0.0;
    $values['non_deductible_amount'] = 0.0;
    $values['date_from']             = 0;
    $values['date_to']               = 9999999999;
    $values['lines'] = array();
    foreach ($this->snapshot_lines as $snapshot_line) {
      $snapshot_line_id = $snapshot_line['id'];
      $receive_date = strtotime($snapshot_line['receive_date']);

      // create line item
      $values['lines'][$snapshot_line_id] = array(
        'id'                           => $snapshot_line['id'],
        'receive_date'                 => $snapshot_line['receive_date'],
        'contribution_id'              => $snapshot_line['contribution_id'],
        'total_amount'                 => $snapshot_line['total_amount'],
        'non_deductible_amount'        => $snapshot_line['non_deductible_amount'],
        // TODO: remove when in financial_type_id snapshot
        'financial_type_id'            => $snapshot_line['financial_type_id'],
        );

      // update general values
      $values['id']        = $snapshot_line_id;    // just use one of them as ID
      $values['currency']  = $snapshot_line['currency'];
      $values['issued_by']             = '';
      if ($receive_date > $values['date_from'])  $values['date_from'] = $receive_date;
      if ($receive_date < $values['date_to'])    $values['date_to'] = $receive_date;
      $values['total_amount'] += $snapshot_line['total_amount'];
      $values['non_deductible_amount'] += $snapshot_line['non_deductible_amount'];
    }

    // TODO: remove lookup when contact_id in snapshot
    $contribution_id = reset($values['lines'])['id'];
    $contribution = civicrm_api3('Contribution', 'getsingle', array('id'=>$snapshot_line['contribution_id']));
    $contact_id = $contribution['contact_id']; 

    // add contributor and addressee
    $values['contributor'] = $this->getContributor($contact_id);
    $values['addressee'] = $this->getAddressee($contact_id);
    
    // add dynamically created tokens
    CRM_Donrec_Logic_ReceiptTokens::addDynamicTokens($values);

    // TODO: remove when done
    error_log("MISSING: ".print_r(CRM_Donrec_Logic_ReceiptTokens::missingTokens($values),1));

    return $values;
  }


  /**
   * Get all properties of this receipt token source needed for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return an array of all properties needed for display
   */
  public function getDisplayTokens() {
    // TODO: optimize
    return $this->getAllTokens();
  }

  /**
   * read out the contributor
   */
  public function getContributor($contact_id) {
    if ($this->cached_contributors[$contact_id]) {
      return $this->cached_contributors[$contact_id];
    }

    // not cached? build it.
    $contributor = array();

    // load the contact
    $contact = new CRM_Contact_BAO_Contact();
    $contact->get('id', $contact_id);

    // copy the base values
    foreach (CRM_Donrec_Logic_ReceiptTokens::$STORED_TOKENS['contributor'] as $key => $value) {
      if (isset($contact->$key)) {
        $contributor[$key] = $contact->$key;
      }
    }

    // add the addresses
    // TODO: get location types from config
    $contributor_address = $this->lookupAddressTokens($contact_id, 0, 0);
    if ($contributor_address != NULL) {
      $contributor = array_merge($contributor, $contributor_address);
    }

    // cache the result
    $this->cached_contributors[$contact_id] = $contributor;

    return $contributor;
  }

  /**
   * get addressee
   */
  public function getAddressee($contact_id) {
    if ($this->cached_addressees[$contact_id]) {
      return $this->cached_addressees[$contact_id];
    }

    // get the addresses
    // TODO: get location types from config
    $addressee = $this->lookupAddressTokens($contact_id, 0, 0);

    // cache the result
    $this->cached_addressees[$contact_id] = $addressee;

    return $addressee;
  }
}
