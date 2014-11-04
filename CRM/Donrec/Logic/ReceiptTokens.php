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
 * This defines an abstract receipt token source, 
 * i.e. data that can be used to create donation receipts
 *
 * current implementations are the donation receipt entity
 * and the snapshot (a temporary donation receipt)
 */
abstract class CRM_Donrec_Logic_ReceiptTokens {

  // these fields of the table get copied into the chunk
  private static $TOKENS = array(
      'id'                        => ts('Receipt ID'),
      'contact_id'                => ts('Contact ID'),
      'contribution_id'           => ts('Contribution ID'),
      'status'                    => ts('Status'),
      'created_by'                => ts('Creator Contact ID'),
      'created_by_display_name'   => ts('Creator Contact'),
      'total_amount'              => ts('Total Amount'),
      'non_deductible_amount'     => ts('Non-deductable Amount'),
      'currency'                  => ts('Currency'),
      'receive_date'              => ts('Receive Date'),
      'receipt_address' => array(),
      'contact_address' => array(),
      'lines' = array(
        'id', 
        'receive_date'
        'contribution_id', 
        'total_amount', 
        'non_deductible_amount', 
        ),
    );
  // private static $CHUNK_FIELDS = array('id', 'contribution_id', 'status', 'created_by', 'total_amount', 'non_deductible_amount', 'currency', 'receive_date', 'contact_id');
  // private static $CONTACT_FIELDS = array('contact_id','display_name', 'street_address', 'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3', 'postal_code', 'city', 'country');
  // private static $LINE_FIELDS = array('id', 'contribution_id', 'status', 'created_by', 'created_timestamp', 'total_amount', 'non_deductible_amount', 'currency', 'receive_date');

  /**
   * creates a multi-level list of all tokens
   */
  public static getTokenList {
    return $self::TOKENS;
  }

  /**
   * Get all properties of this receipt token source, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * @return array of properties
   */
  public abstract function getAllTokens();

   * Get all the properties of this receipt needed for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return an array of all properties needed for display

  /**
   * Get all properties of this receipt token sourceneeded for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return an array of all properties needed for display
   */
  public abstract function getDisplayTokens();
}
