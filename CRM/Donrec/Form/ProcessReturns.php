<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

use CRM_Donrec_ExtensionUtil as E;

/**
 * The form allows the collection of returns from the
 * "send by email" process and to create activities
 */
class CRM_Donrec_Form_ProcessReturns extends CRM_Core_Form {
  public function buildQuickForm() {

    // parser data
    $this->add(
        'select',
        'returns_activity_type_id',
        E::ts('Activity Type'),
        $this->getActivityTypes(),
        FALSE
    );
//    $this->add(
//        'text',
//        'returns_pattern',
//        E::ts('Scanner Pattern'),
//        ['class' => 'huge'],
//        FALSE
//    );
    $this->add(
        'text',
        'returns_activity_subject',
        E::ts('Activity Subject'),
        ['class' => 'huge'],
        FALSE
    );

    $this->add(
        'text',
        'returns_limit',
        E::ts('Limit'),
        [],
        TRUE
    );
    $this->add(
        'checkbox',
        'returns_withdraw',
        E::ts('Withdraw Receipts')
    );


    // add login data
    $this->add(
      'text',
      'returns_server',
      E::ts('Server'),
      [],
      TRUE
    );
    $this->add(
        'text',
        'returns_user',
        E::ts('User Name'),
        [],
        TRUE
    );
    $this->add(
        'text',
        'returns_pass',
        E::ts('Password'),
        [],
        TRUE
    );

    // set defaults
    $defaults = CRM_Core_BAO_Setting::getItem('de.systopia.donrec', 'donrec_returns');
    if (!empty($defaults)) {
      $this->setDefaults($defaults);
    }

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Run Now'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    parent::buildQuickForm();
  }

  public function validate() {
    // validate scanner pattern
//    if (!empty($this->_submitValues['returns_pattern'])) {
//      $pattern = $this->_submitValues['returns_pattern'];
//      // needs to contain '(?P<contact_id>'
//      if (!strstr($pattern, '(?P<contact_id>')) {
//        $this->_errors['returns_pattern'] = E::ts("Pattern needs to contain a named group 'contact_id', e.g. (?P<contact_id>[0-9]+).");
//      }
//      if (substr($pattern, 0, 1) != substr($pattern, strlen($pattern) - 1, 1)) {
//        $this->_errors['returns_pattern'] = E::ts("RegEx patterns need a delimiter at the beginning and end!");
//      }
//
//      // first and last letter have to be the same
//      if (substr($pattern, 0, 1) != substr($pattern, strlen($pattern) - 1, 1)) {
//        $this->_errors['returns_pattern'] = E::ts("RegEx patterns need a delimiter at the beginning and end!");
//      }
//    }

    $limit = (int) $this->_submitValues['returns_limit'];
    if ($limit < 1) {
      $this->_errors['returns_limit'] = E::ts("Limit needs to be greater than zero.");
    }

    return parent::validate();
  }

  public function postProcess() {
    $params = $this->exportValues();

    // store defaults
    $defaults = $params;
    unset($defaults['qfKey']);
    unset($defaults['entryURL']);
    unset($defaults['returns_pass']);
    CRM_Core_BAO_Setting::setItem($defaults, 'de.systopia.donrec', 'donrec_returns');

    // run
    try {
      $result = civicrm_api3('DonationReceiptEngine', 'processreturns', [
          'hostname'         => $params['returns_server'],
          'username'         => $params['returns_user'],
          'password'         => $params['returns_pass'],
          'activity_type_id' => $params['returns_activity_type_id'],
          'activity_subject' => $params['returns_activity_subject'],
          'limit'            => (int)$params['returns_limit'],
          'withdraw'         => empty($params['returns_withdraw']) ? '0' : '1',
      ])['values'];

      // let the user know
      $message = E::ts("Investigated %1 emails in the bounce folder. %2 of them have been processed, %3 seem to unrelated, and %4 have failed.", [
          1 => $result['count'],
          2 => $result['processed'],
          3 => $result['ignored'],
          4 => $result['failed']]);
      CRM_Core_Session::setStatus($message, E::ts("Done"), 'info');

      if ($result['failed']) {
        $error_message = E::ts("If you want to find out, why processing of %1 of the emails failed, please check the CiviCRM log.",
            [1 => $result['failed']]);
        CRM_Core_Session::setStatus($error_message, E::ts("Errors"), 'error');
      }

      if ($result['limit'] == $result['count']) {
        CRM_Core_Session::setStatus(E::ts("It looks like there are more emails to be processed."), E::ts("There's more"), 'warn');
      }
    } catch(Exception $ex) {
      CRM_Core_Session::setStatus(E::ts("There's a problem with the returns processor: " . $ex->getMessage()), E::ts("Error"), 'error');
    }

    parent::postProcess();
  }

  /**
   * Get all activity types
   */
  protected function getActivityTypes() {
    $activity_types = ['' => E::ts("No Activity")];
    $activity_type_query = civicrm_api3('OptionValue','get', [
            'option.limit'    => 0,
            'option_group_id' => 'activity_type']);
    foreach ($activity_type_query['values'] as $value) {
      $activity_types[$value['value']] = $value['label'];
    }
    return $activity_types;
  }

  /**
   * Identify the contact based on email address and/or content
   * @param $email_address string email address of the recipient
   * @param $email_body    string email content (including headers)
   * @param $params        array  general parameters, including 'returns_pattern'
   *
   * @return array contact IDs
   */
  protected function identifyContact($email_address, $email_body, $params) {
    $contact_id_list = [];
    try {
      if (empty($params['returns_pattern'])) {
        // the user has not provided a pattern, so we need to go with the
        //  email address
        if (!empty($email_address)) {
          $contacts = civicrm_api3('Contact', 'get', [
              'option.limit' => 1,
              'email'        => $email_address,
              'return'       => 'id']);
          foreach ($contacts['values'] as $contact) {
            $contact_id_list[] = $contact['id'];
          }
        }

      } else {
        // there is a pattern => use it to identify the contact
        if (preg_match($params['returns_pattern'], $email_body, $matches)) {
          if (!empty($matches['contact_id'])) {
            // see if the contact still exists
            if (function_exists('identitytracker_civicrm_enable')) {
              // identity tracker is active -> use that
              $contact_search = civicrm_api3('Contact', 'findbyidentity', [
                  'identifier'      => $matches['contact_id'],
                  'identifier_type' => 'internal']);
              foreach ($contact_search['values'] as $contact) {
                $contact_id_list[] = $contact['id'];
              }

            } else {
              // identity tracker not available -> simply check if not deleted
              $contact = civicrm_api3('Contact', 'getsingle', [
                  'id'     => $matches['contact_id'],
                  'return' => 'id,is_deleted']);
              if (empty($contact['is_deleted'])) {
                $contact_id_list[] = $contact['id'];
              }
            }
          }
        }
      }
    } catch(Exception $ex) {
      CRM_Core_Error::debug_log_message("DonrecReturns: Troubles looking up contact: " . $ex->getMessage());
    }
    return $contact_id_list;
  }

}
