<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class represents a single SnapShot line as a single, temparary receipt
 * or a list of snapshot lines in case of the bulk receipt
 */
class CRM_Donrec_Logic_SnapshotReceipt extends CRM_Donrec_Logic_ReceiptTokens {

  /**
   * @var \CRM_Donrec_Logic_Snapshot $snapshot
   */
  protected $snapshot;
  protected $snapshot_lines;
  protected $is_test;
  protected $receipt_id;

  private $cached_contributors = array();
  private $cached_addressees = array();

  public function __construct($snapshot, $snapshot_lines, $is_test) {
    $this->snapshot = $snapshot;
    $this->snapshot_lines = $snapshot_lines;
    $this->is_test = $is_test;

    // generate receiptID
    $pattern = $this->getProfile()->getDataAttribute('id_pattern');
    $id_generator = new CRM_Donrec_Logic_IDGenerator($pattern, $this->is_test);
    $this->receipt_id = $id_generator->generateID($snapshot_lines);
  }

  public function isBulk() {
    return count($this->snapshot_lines) > 1;
  }

  public function getLine() {
    return $this->snapshot->getLine($this->getID());
  }

  /**
   * gets the line ID of the first line
   *
   * @return snapshot line ID
   */
  public function getID() {
    return reset($this->snapshot_lines)['id'];
  }

  /**
   * gets the line ID of the first line
   *
   * @return snapshot line ID
   */
  public function getReceiptID() {
    return $this->receipt_id;
  }

  /**
   * gets the line IDs
   *
   * @return snapshot line IDs
   */
  public function getIDs() {
    foreach ($this->snapshot_lines as $snapshot_line) {
      $line_ids[] = $snapshot_line['id'];
    }
    return $line_ids;
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
   * gets the ContactID of the first line
   *
   * @return contact ID
   */
  public function getContactID() {
    return reset($this->snapshot_lines)['contact_id'];
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
    $values['receipt_id']            = $this->receipt_id;
    $values['status']                = $this->is_test?'DRAFT':'ORIGINAL';
    $values['issued_on']             = date('Y-m-d H:i:s');
    $values['issued_by']             = CRM_Core_Session::singleton()->get('userID');
    $values['total_amount']          = 0.0;
    $values['non_deductible_amount'] = 0.0;
    $values['date_from']             = 9999999999;
    $values['date_to']               = 0;
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
        'financial_type_id'            => $snapshot_line['financial_type_id'],
        );

      // update general values
      $values['id']                     = $snapshot_line_id;    // just use one of them as ID
      $values['profile']                = $snapshot_line['profile'];
      $values['contact_id']             = $snapshot_line['contact_id'];
      $values['currency']               = $snapshot_line['currency'];
      $values['date_from']              = $snapshot_line['date_from'];
      $values['date_to']                = $snapshot_line['date_to'];
      $values['total_amount']          += $snapshot_line['total_amount'];
      $values['non_deductible_amount'] += $snapshot_line['non_deductible_amount'];
    }

    // add contributor and addressee
    $values['contributor'] = $this->getContributor($values['contact_id']);
    $values['addressee']   = $this->getAddressee($values['contact_id']);

    // add dynamically created tokens
    CRM_Donrec_Logic_ReceiptTokens::addDynamicTokens($values, self::getProfile());

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
    $types = $this->getProfile()->getLocationTypes()['legal'];
    $contributor_address = $this->lookupAddressTokens($contact_id, $types['address'], $types['fallback']);
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
    $types = $this->getProfile()->getLocationTypes()['postal'];

    // FIXME: if the contributor-address has the same type, this will result in
    // an unnecessary database-request.
    // An extra address-cache would be fine.
    $addressee = $this->lookupAddressTokens($contact_id, $types['address'], $types['fallback']);

    // cache the result
    $this->cached_addressees[$contact_id] = $addressee;

    return $addressee;
  }

  /**
   * get the profile object that was used to create this receipt
   *
   * @return \CRM_Donrec_Logic_Profile
   */
  public function getProfile() {
    return $this->snapshot->getProfile();
  }
}
