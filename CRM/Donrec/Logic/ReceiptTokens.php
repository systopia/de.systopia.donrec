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
  /**
   * This is the list (and structure) of the tokens, that will be 
   * generated and stored in the recipt items
   */
  private static $STORED_TOKENS = array(
      'id'                        => ts('Receipt ID'),
      'status'                    => ts('Status'),
      'created_by'                => ts('Creator Contact ID'),
      'created_by_display_name'   => ts('Creator Contact'),
      'total_amount'              => ts('Total Amount'),
      'totaltext'                 => ts('Total Amount In Words'),
      'today'                     => is('Issue Date'),              // naming for compatibility reasons
      'non_deductible_amount'     => ts('Non-deductable Amount'),
      'currency'                  => ts('Currency'),
      'receive_date'              => ts('Receive Date'),
      'lines' = array(            // (MUTIPLE!) INDIVIDUAL LINES (in case of BULK receipt)
        'receive_date'                 => ts('Receive Date'),
        'contribution_id',             => ts('Contribution ID'), 
        'total_amount',                => ts('Total Amount'), 
        'non_deductible_amount',       => ts('Non-deductible Amount'),
        'financial_type_id',           => ts('Financial Type ID'), 
        ),
      'contributor' => array(     // LEGAL ADDRESS OF THE DONOR
        'id'                           => ts('Contact ID'),
        'contact_type'                 => ts('Contact Type'),
        'display_name'                 => ts('Display Name'),
        'addressee'                    => ts('Addressee'),
        'postal_greeting'              => ts('Postal Greeting'),
        'email_greeting'               => ts('Email Greeting'),
        'gender'                       => ts('Gender'),
        'prefix'                       => ts('Prefix'),
        'supplemental_address_1'       => ts('Supplemental Address 1'),
        'supplemental_address_2'       => ts('Supplemental Address 2'),
        'postal_code'                  => ts('Postal Code'),
        'city'                         => ts('City'),
        'country'                      => ts('Country'),
        ),
      'addressee' => array(       // POSTAL ADDRESS OF THE RECEIPIENT
        'display_name'                 => ts('Display Name'),
        'addressee'                    => ts('Addressee'),
        'supplemental_address_1'       => ts('Supplemental Address 1'),
        'supplemental_address_2'       => ts('Supplemental Address 2'),
        'postal_code'                  => ts('Postal Code'),
        'city'                         => ts('City'),
        'country'                      => ts('Country'),
        ),
    );
  
  /**
   * This is the list (and structure) of the tokens, 
   * that will be NOT stored in the recipt items,
   * but rather created on-the-fly. 
   * However, in most cases it will be based on the stored data above
   */
  private static $DYNAMIC_TOKENS = array(
      'created_by_display_name'   => ts('Creator Contact'),
      'total'                     => ts('Total Amount'),           // naming for compatibility reasons
      'totaltext'                 => ts('Total Amount In Words'),
      'totalmoney'                => ts('Total Amount (formatted)'),
      'lines' = array(            // INDIVIDUAL LINES (in case of BULK receipt)
        'financial_type',              => ts('Financial Type'), 
        ),
      'contributor' => array(     // LEGAL ADDRESS OF THE DONOR
        ),
      'addressee' => array(       // POSTAL ADDRESS OF THE RECEIPIENT
        ),
      'organisation' => array(    // ISSUING (DEFAULT) ORGANISATION   =>        
        'display_name'                 => ts('Display Name'),
        'addressee'                    => ts('Addressee'),
        'supplemental_address_1'       => ts('Supplemental Address 1'),
        'supplemental_address_2'       => ts('Supplemental Address 2'),
        'postal_code'                  => ts('Postal Code'),
        'city'                         => ts('City'),
        'country'                      => ts('Country'),
        ),
    );  


  /**
   * Get all properties of this receipt token source, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * @return array of properties
   */
  public abstract function getAllTokens();


  /**
   * Get all properties of this receipt token sourceneeded for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return an array of all properties needed for display
   */
  public abstract function getDisplayTokens();



  /****************************************************************************
   **                             HELPER FUNCTIONS                           **
   ***************************************************************************/

  /**
   * creates a multi-level list of all tokens
   */
  public static function getFullTokenList() {
    $tokens = $self::STORED_TOKENS;
    foreach ($self::DYNAMIC_TOKENS as $key => $value) {
      if (is_array($value)) {
        $tokens[$key] = array_merge($tokens[$key], $value);
      } else {
        $tokens[$key] = $value;
      }
    }
    return $tokens;
  }

  /*
   * Takes a full list of token -> values and 
   * adds the dynamic tokens 
   */
  public static function addDynamicTokens(&$values) {
    if (!empty($values['created_by'])) {
      // add created_by_display_name
      try {
        $creator = civicrm_api3('Contact', 'getsingle', array('id' => $values['created_by']));
        $values['created_by_display_name'] = $creator['display_name'];
      } catch (Exception $e) {
        error_log('de.systopia.donrec - '.print_r($e,1));
      }
    }

    // add the monetary tokens: 'total', 'totaltext', 'totalmoney'
    if (isset($values['total_amount'])) {
      $values['amount'] = $values['total_amount'];
      $values['totaltext'] = CRM_Utils_DonrecHelper::convert_number_to_words($values['total_amount']);
      $values['totalmoney'] =  CRM_Utils_Money::format($values['total_amount'], $values['currency']);
    }

    // add financial type name
    $financialTypes  = CRM_Contribute_PseudoConstant::financialType();
    error_log(print_r($financialType,1));
    if (is_array($values['lines'])) {
      foreach ($values['lines'] as $key => $line) {
        if (!empty($line['financial_type_id'])) {
          // TODO: is that correct?
          $values['lines'][$key]['financial_type'] = $financialTypes[$line['financial_type_id']];
        }
      }
    }

    // add organisation address
    if (empty($values['organisation'])) {
      $domain = CRM_Core_BAO_Domain::getDomain();
      $values['organisation'] = $self::lookupAddressTokens($domain->contact_id, 0, 0);
    }
  }

  /**
   * HELPER to verify that all the STORED_TOKENS have been set in the given value array
   * 
   * @return an array with all missing tokens
   */
  public static function missingTokens($values) {
    $missing_tokens = array();
    $expected_tokens = $self::getFullTokenList();
    foreach ($expected_tokens as $key => $value) {
      if (is_array($value) {
        if ($key=='lines') {
          foreach ($value as $line_id => $line) {
            foreach ($line as $key2 => $value2) {
              if (!isset($values['lines'][$line_id][$key2])) {
                $missing_tokens['lines'][$line_id][$key2] = $value2;
              }
            }          
          }
        } else {
          foreach ($value as $key2 => $value2) {
            if (!isset($values[$key][$key2])) {
              $missing_tokens[$key][$key2] = $value2;
            }
          }          
        }
      } else {
        if (!isset($values[$key])) {
          $missing_tokens[$key] = $value;
        }
      }
    }

    return $missing_tokens;
  }

  /**
   * Get address tokens for a given contact with fallback type
   */
  public static function lookupAddressTokens($contact_id, $location_type, $fallback_location_type) {
    if (empty($contact_id)) return array();
    
    // find the address
    $address = $self::_lookupAddress($contact_id, $location_type);
    if ($address == NULL) {
      $address = $self::_lookupAddress($contact_id, $fallback_location_type);
    }

    if ($address == NULL) {
      // no address found
      return array();
    }

    //add contact information
    $contact_bao = new CRM_Contact_BAO_Contact();
    $contact_bao->get('id', $contact_id);
    error_log(print_r($contact_bao,1));
    $address['display_name'] = $contact_bao->display_name;
    $address['addressee'] = $contact_bao->addressee_display;

    return $address;
  }

  /**
   * Get address tokens for a given contact
   */
  private static function _lookupAddress($contact_id, $location_type) {
    if (empty($contact_id)) return NULL;

    // compile query
    $query_params['contact_id'] $contact_id,
    if (empty($location_type)) {
      $query_params['is_primary'] = 1;
    } else {
      $query_params['location_type_id'] = $location_type;
    }

    // execute the query
    try {
      $address_found = civicrm_api3('Address', 'getsingle', $query_params);
      $address['postal_code'] = $address_found['postal_code'];
      $address['city'] = $address_found['city'];
      $address['country'] = $address_found['country'];
      $address['supplemental_address_1'] = $address_found['supplemental_address_1'];
      $address['supplemental_address_2'] = $address_found['supplemental_address_2'];
      return $address;
    } catch (Exception $e) {
      // address does not exist
      return NULL;
    }    
  }
}
