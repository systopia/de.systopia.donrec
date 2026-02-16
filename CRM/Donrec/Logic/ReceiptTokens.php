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

/**
 * This defines an abstract receipt token source,
 * i.e. data that can be used to create donation receipts
 *
 * current implementations are the donation receipt entity
 * and the snapshot (a temporary donation receipt)
 *
 * phpcs:disable Generic.NamingConventions.AbstractClassNamePrefix.Missing
 */
abstract class CRM_Donrec_Logic_ReceiptTokens {
  /**
   * This is the list (and structure) of the tokens, that will be
   * generated and stored in the recipt items
   */
  protected static array $STORED_TOKENS = [
    'id'                        => 'Receipt ID',
    'receipt_id'                => 'Custom Receipt ID',
    'profile_id'                => 'Profile ID',
    'status'                    => 'Status',
    'type'                      => 'Single or bulk',
    'exporters'                 => 'Exporter type',
    'issued_by'                 => 'Creator Contact ID',
    'issued_on'                 => 'Issued Date',
    'total_amount'              => 'Total Amount',
    'non_deductible_amount'     => 'Non-deductable Amount',
    'currency'                  => 'Currency',
    'date_from'                 => 'Contribution Receive Date From',
    'date_to'                   => 'Contribution Receive Date To',
    'original_file'             => 'Stores the originial PDF file',
  // (MUTIPLE!) INDIVIDUAL LINES (in case of BULK receipt)
    'lines' => [
      'receive_date'                 => 'Receive Date',
      'contribution_id'              => 'Contribution ID',
      'total_amount'                 => 'Total Amount',
      'non_deductible_amount'        => 'Non-deductible Amount',
      'financial_type_id'            => 'Financial Type ID',
    ],
    // LEGAL ADDRESS OF THE DONOR
    'contributor' => [
      'id'                           => 'Contact ID',
      'contact_type'                 => 'Contact Type',
      'display_name'                 => 'Display Name',
      'addressee_display'            => 'Addressee',
      'street_address'               => 'Street Address',
      'postal_greeting_display'      => 'Postal Greeting',
      'email_greeting_display'       => 'Email Greeting',
      'gender'                       => 'Gender',
      'prefix'                       => 'Prefix',
      'supplemental_address_1'       => 'Supplemental Address 1',
      'supplemental_address_2'       => 'Supplemental Address 2',
      'postal_code'                  => 'Postal Code',
      'city'                         => 'City',
      'country'                      => 'Country',
    ],
    // POSTAL ADDRESS OF THE RECEIPIENT
    'addressee' => [
      'display_name'                 => 'Display Name',
      'addressee_display'            => 'Addressee',
      'street_address'               => 'Street Address',
      'supplemental_address_1'       => 'Supplemental Address 1',
      'supplemental_address_2'       => 'Supplemental Address 2',
      'postal_code'                  => 'Postal Code',
      'city'                         => 'City',
      'country'                      => 'Country',
    ],
  ];

  /**
   * This is the list (and structure) of the tokens,
   * that will be NOT stored in the recipt items,
   * but rather created on-the-fly.
   * However, in most cases it will be based on the stored data above
   */
  protected static array $DYNAMIC_TOKENS = [
    'issued_by_display_name'    => 'Creator Contact',
  // naming for compatibility reasons
    'total'                     => 'Total Amount',
    'totaltext'                 => 'Total Amount In Words',
    'totalmoney'                => 'Total Amount (formatted)',
    'today'                     => 'Issue Date',
    'items'                     => 'the same as lines, but only if BULK',
    'view_url'                  => 'URL to downlaod an ORIGINAL PDF - if exists',
  // INDIVIDUAL LINES
    'lines' => [
      'financial_type'               => 'Financial Type',
    ],
    // LEGAL ADDRESS OF THE DONOR
    'contributor' => [],
    // POSTAL ADDRESS OF THE RECIPIENT
    'addressee' => [],
    // phpcs:disable Squiz.PHP.CommentedOutCode.Found
    // ISSUING (DEFAULT) ORGANISATION
    // phpcs:enable
    'organisation' => [
      'display_name'                 => 'Display Name',
      'addressee'                    => 'Addressee',
      'supplemental_address_1'       => 'Supplemental Address 1',
      'supplemental_address_2'       => 'Supplemental Address 2',
      'postal_code'                  => 'Postal Code',
      'city'                         => 'City',
      'country'                      => 'Country',
    ],
  ];

  /**
   * Get all properties of this receipt token source, so we can e.g. export it or pass the
   * properties into the $template->generatePDF() function to create another copy
   *
   * @return array of properties
   */
  abstract public function getAllTokens();

  /**
   * Get all properties of this receipt token sourceneeded for display in the summary tab
   *
   * This should only include the display properties, and be performance optimized
   *
   * @return array an array of all properties needed for display
   */
  abstract public function getDisplayTokens();

  /****************************************************************************
   *                              HELPER FUNCTIONS                            *
   */

  /**
   * creates a multi-level list of all tokens
   */
  public static function getFullTokenList() {
    $tokens = self::$STORED_TOKENS;
    foreach (self::$DYNAMIC_TOKENS as $key => $value) {
      if (is_array($value) && is_array($tokens[$key] ?? NULL)) {
        $tokens[$key] = array_merge($tokens[$key], $value);
      }
      else {
        $tokens[$key] = $value;
      }
    }
    return $tokens;
  }

  /**
   *
   * Takes a full list of token -> values and
   * adds the dynamic tokens
   *
   * phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
   *
   */
  public static function addDynamicTokens(&$values, $profile) {
  // phpcs:enable
    $language = CRM_Donrec_Lang::getLanguage(NULL, $profile);

    if (!empty($values['issued_by'])) {
      // add created_by_display_name
      try {
        $creator = civicrm_api3('Contact', 'getsingle', ['id' => $values['issued_by']]);
        $values['issued_by_display_name'] = $creator['display_name'];
      }
      catch (Exception $e) {
        // @ignoreException
        Civi::log()->debug('de.systopia.donrec - ' . print_r($e, TRUE));
      }
    }

    // add the legacy 'today' token
    if (!empty($values['issued_on'])) {
      $values['today'] = $values['issued_on'];
    }

    // add the monetary tokens: 'total', 'totaltext', 'totalmoney'
    if (isset($values['total_amount'])) {
      // format total_amount
      $values['total_amount'] = number_format((float) $values['total_amount'], 2, '.', '');
      $values['total']        = $values['total_amount'];
      $values['totaltext']    = $language->amount2words($values['total_amount'], $values['currency']);
      $values['totalmoney']   = CRM_Utils_Money::format($values['total_amount'], '');
    }

    // add financial type name and initialize $sorted_lines
    $financialTypes = Civi::entity('Contribution')->getOptions('financial_type_id');
    assert(NULL !== $financialTypes);
    /** @var array<int, string> $financialTypes */
    $financialTypes = array_column($financialTypes, 'label', 'id');
    if (!empty($values['lines']) && is_array($values['lines'])) {
      foreach ($values['lines'] as $key => $line) {
        if (!empty($line['financial_type_id'])) {
          $values['lines'][$key]['financial_type'] = $financialTypes[$line['financial_type_id']];
        }
      }
      $sorted_lines = $values['lines'];
    }
    else {
      $sorted_lines = [];
    }

    // sort contribution lines by receive date (#1497)
    $receive_dates = [];
    foreach ($sorted_lines as $key => $line) {
      $sorted_lines[$key]['id'] = $key;
      $receive_dates[$key] = $line['receive_date'];
    }
    array_multisort($receive_dates, SORT_ASC, SORT_REGULAR, $sorted_lines);
    $values['lines'] = [];
    foreach ($sorted_lines as $line) {
      $values['lines'][$line['id']] = $line;
    }

    // add legacy 'items'
    if (count($values['lines']) > 1) {
      $values['items'] = $values['lines'];
    }

    // add organisation address
    if (empty($values['organisation'])) {
      $domain = CRM_Core_BAO_Domain::getDomain();
      $values['organisation'] = self::lookupAddressTokens((int) $domain->contact_id, 0, 0);
    }

    // ADD watermarks
    $profile = CRM_Donrec_Logic_Profile::getProfile($values['profile_id'] ?? NULL);
    // phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if (isset($values['status']) && $values['status'] == 'ORIGINAL') {
    // phpcs:enable
      // nothing to do in this case.
    }
    elseif (isset($values['status']) && $values['status'] == 'COPY') {
      $values['watermark'] = $profile->getDataAttribute('copy_text');
    }
    else {
      // in all other cases, it's INVALID/DRAFT:
      $values['watermark'] = $profile->getDataAttribute('draft_text');
    }

    // copy contributor values to addressee, if not set separately
    if (!isset($values['addressee']['display_name'])) {
      $values['addressee']['display_name'] = $values['contributor']['display_name'] ?? NULL;
    }
    if (!isset($values['addressee']['addressee_display'])) {
      $values['addressee']['addressee_display'] = $values['contributor']['addressee_display'] ?? NULL;
    }

    // add URL to view original file, if it exists
    if (!empty($values['original_file'])) {
      $values['view_url'] = CRM_Donrec_Logic_File::getPermanentURL(
        $values['original_file'],
        $values['contributor']['id']
      );
    }

    // see if there is any additional tokens
    CRM_Donrec_Logic_Hooks::donationReceiptTokenValues($values);
  }

  /**
   * HELPER to verify that all the STORED_TOKENS have been set in the given value array
   *
   * @param array $values
   *
   * @return array
   *   array with all missing tokens
   */
  public static function missingTokens($values) {
    $missing_tokens = [];
    $expected_tokens = self::getFullTokenList();
    foreach ($expected_tokens as $key => $value) {
      if (is_array($value)) {
        if ($key == 'lines') {
          // simply check the first line
          reset($values['lines']);
          $first_line_id = key($values['lines']);
          foreach ($value as $line_key => $line_value) {
            if (!isset($values['lines'][$first_line_id][$line_key])) {
              $missing_tokens['lines'][$line_key] = $line_value;
            }
          }
        }
        else {
          foreach ($value as $key2 => $value2) {
            if (!isset($values[$key][$key2])) {
              $missing_tokens[$key][$key2] = $value2;
            }
          }
        }
      }
      else {
        if (!isset($values[$key])) {
          $missing_tokens[$key] = $value;
        }
      }
    }

    return $missing_tokens;
  }

  /**
   * Get address tokens for a given contact with fallback type
   * @param int $contact_id
   * @param $location_type
   * @param $fallback_location_type
   * @return array|null
   */
  public static function lookupAddressTokens($contact_id, $location_type, $fallback_location_type) {
    if (empty($contact_id)) {
      return [];
    }

    // find the address
    $address = self::_lookupAddress($contact_id, $location_type);
    if ($address == NULL) {
      $address = self::_lookupAddress($contact_id, $fallback_location_type);
    }

    if ($address == NULL) {
      // no address found
      return [];
    }

    //add contact information
    $contact_bao = new CRM_Contact_BAO_Contact();
    $contact_bao->get('id', $contact_id);
    $address['display_name'] = $contact_bao->display_name;
    $address['addressee'] = $contact_bao->addressee_display;

    return $address;
  }

  /**
   * Get address tokens for a given contact
   * @param int $contact_id
   * @param $location_type
   * @return array | null
   */
  private static function _lookupAddress($contact_id, $location_type) {
    if (empty($contact_id)) {
      return NULL;
    }

    // compile query
    $query_params['contact_id'] = $contact_id;
    if (empty($location_type)) {
      $query_params['is_primary'] = 1;
    }
    else {
      $query_params['location_type_id'] = $location_type;
    }
    // execute the query
    try {
      $address_found = civicrm_api3('Address', 'getsingle', $query_params);
      $address['street_address'] = $address_found['street_address'] ?? '';
      $address['postal_code']    = $address_found['postal_code'] ?? '';
      $address['city']           = $address_found['city'] ?? '';
      if (!empty($address_found['country_id'])) {
        $country = CRM_Core_PseudoConstant::country($address_found['country_id']);
        $address['country'] = $country;
      }
      $address['supplemental_address_1'] = $address_found['supplemental_address_1'] ?? '';
      $address['supplemental_address_2'] = $address_found['supplemental_address_2'] ?? '';
      return $address;
    }
    catch (Exception $e) {
      // @ignoreException
      // address does not exist
      return NULL;
    }
  }

}
