<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)            |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

require_once 'donrec.civix.php';
require_once 'CRM/Donrec/DataStructure.php';
require_once 'CRM/Donrec/Logic/Template.php';
require_once 'CRM/Donrec/Logic/Settings.php';

use CRM_Donrec_ExtensionUtil as E;

/**
 * Implementation of hook_civicrm_config
 */
function donrec_civicrm_config(&$config) {
  _donrec_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_install
 */
function donrec_civicrm_install() {
  return _donrec_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_enable
 */
function donrec_civicrm_enable() {
  return _donrec_civix_civicrm_enable();
}

/**
* Add an action for creating donation receipts after doing a search
*
* @param string $objectType specifies the component
* @param array $tasks the list of actions
*
* @access public
*/
function donrec_civicrm_searchTasks($objectType, &$tasks) {
  // add DONATION RECEIPT task to contact list
  if ($objectType == 'contact') {
    if (CRM_Core_Permission::check('create and withdraw receipts')) {
      $tasks[] = array(
          'title' => E::ts('Issue donation receipt(s)'),
          'class' => 'CRM_Donrec_Form_Task_DonrecTask',
          'result' => false);
      $tasks[] = array(
          'title' => E::ts('Withdraw donation receipt(s)'),
          'class' => 'CRM_Donrec_Form_Task_DonrecResetTask',
          'result' => false);
    }
  }

  // add REBOOK task to contribution list
  if ($objectType == 'contribution') {
    if (CRM_Core_Permission::check('create and withdraw receipts')) {
      $tasks[] = array(
          'title' => E::ts('Issue donation receipt(s)'),
          'class' => 'CRM_Donrec_Form_Task_ContributeTask',
          'result' => false);
    }
    if (CRM_Core_Permission::check('edit contributions')) {
      $tasks[] = array(
          'title'  => E::ts('Rebook to contact'),
          'class'  => 'CRM_Donrec_Form_Task_RebookTask',
          'result' => false);
    }
  }
}

/**
 *  1) add an extra search column 'receipted'
 *  2) modify actions for rebook
 */
function donrec_civicrm_searchColumns($objectName, &$headers,  &$values, &$selector) {
  if ($objectName == 'contribution') {
    // ************************************
    // **      ADD CONTRIBUTED COLUMN    **
    // ************************************

    // save last element (action list)
    $actionList = array_pop($headers);
    // insert new column
    $headers[] = array(
      'name' => E::ts('Receipted'),
      // Provide a weight lower than the "actions" column.
      'weight' => $actionList['weight'] - 1,
      'field_name' => 'is_receipted',
    );

    $receipted_contribution_ids = array();

    // insert new values
    foreach ($values as $id => $value ) {
      $item_id = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($value['contribution_id'], TRUE);
      if($item_id === FALSE) {
        $values[$id]['is_receipted'] = E::ts('No');
      }else{
        $receipted_contribution_ids[] = $value['contribution_id'];    // save as receipted for rebook (see below)
        $values[$id]['is_receipted'] = sprintf('<a href="%s">%s</a>', CRM_Utils_System::url(
        'civicrm/contact/view',
        "reset=1&cid={$value['contact_id']}&rid=$item_id&selectedChild=donation_receipts",
        TRUE, NULL, TRUE),
        E::ts('Yes'));
      }
    }
    // restore last element
    $headers[] = $actionList;


    // ************************************
    // **       ADD REBOOK ACTION        **
    // ************************************
    // only offer rebook only if the user has the correct permissions
    if (CRM_Core_Permission::check('edit contributions')) {
      $contribution_status_complete = (int) CRM_Donrec_CustomData::getOptionValue('contribution_status', 'Completed', 'name');
      $title = E::ts('Rebook');
      $url = CRM_Utils_System::url('civicrm/donrec/rebook', "contributionIds=__CONTRIBUTION_ID__");
      $action = "<a title=\"$title\" class=\"action-item action-item\" href=\"$url\">$title</a>";

      // add 'rebook' action link to each row
      foreach ($values as $rownr => $row) {
        $contribution_status_id = $row['contribution_status_id'];
        // ... but only for completed contributions
        if ($contribution_status_id==$contribution_status_complete) {
          // receipted contributions cannot be rebooked either...
          if (!in_array($row['contribution_id'], $receipted_contribution_ids)) {
            // this contribution is o.k. => add the rebook action
            $contribution_id = $row['contribution_id'];
            $this_action = str_replace('__CONTRIBUTION_ID__', $contribution_id, $action);
            if (strpos($row['action'], '</ul>') !== FALSE) {
              $values[$rownr]['action'] = str_replace('</ul></span>', '<li>'.$this_action.'</li></ul></span>', $row['action']);
            }
            else {
              $values[$rownr]['action'] = str_replace('</span>', $this_action.'</span>', $row['action']);
            }
          }
        }
      }
    }
  }
}

/**
 * The extra search column (see above) does not alter the template,
 * so we inject javascript into the template-content.
 */
function donrec_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  // The contribution search results template has changed in 4.7.20, allowing
  // to add columns and values in hook_civicrm_searchColumns() implementations.
  // Prior to 4.7.20, this has to be done using the following approach.
  // @link https://lab.civicrm.org/dev/core/commit/4fb5fcf3b17af6c9f5bf49ecc69902c5b0b78c24
  // Commit that introduced the new behavior.
  if (version_compare(CRM_Utils_System::version(), '4.7.20', '<')) {
    // get page- resp. form-class of the object
    $class_name = get_class($object);
    if ($class_name == 'CRM_Contribute_Page_Tab' ||
      $class_name == 'CRM_Contribute_Form_Search' ||
      $class_name == 'CRM_Contribute_Page_DashBoard') {
      // parse the template with smarty
      $smarty = CRM_Core_Smarty::singleton();
      $path = __DIR__ . '/templates/CRM/Contribute/ReceiptedColumn.tpl';
      $html = $smarty->fetch($path);
      // append the html to the content
      $content .= $html;
    }
  }
}

/**
 * Set permissions for runner/engine API call
 */
function donrec_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  // TODO: adjust to correct permission
  $permissions['donation_receipt_engine']['next'] = array('create and withdraw receipts');
  $permissions['donation_receipt']['view'] = array('view and copy receipts');
  $permissions['donation_receipt']['delete'] = array('delete receipts');
  $permissions['donation_receipt']['copy'] = array('view and copy receipts');
  $permissions['donation_receipt']['withdraw'] = array('create and withdraw receipts');
}

/**
 * Implements hook_civicrm_permission().
 */
function donrec_civicrm_permission(&$permissions) {
  $prefix = E::ts('DonationReceipts') . ': ';
  $permissions['view and copy receipts'] = [
    'label' => $prefix . E::ts('view and create copies of receipts'),
    'description' => E::ts('Allows viewing and creating copies of donation receipts.'),
  ];
  $permissions['create and withdraw receipts'] = [
    'label' => $prefix . E::ts('create and withdraw receipts'),
    'description' => E::ts('Allows creating and withdrawing donation receipts.'),
  ];
  $permissions['delete receipts'] = [
    'label' => $prefix . E::ts('delete receipts'),
    'description' => E::ts('Allows deleting donation receipts.'),
  ];
}

/**
 * Add headers to sent donation receipts
 */
function donrec_civicrm_alterMailParams(&$params, $context) {
  CRM_Donrec_Exporters_EmailPDF::addDonrecMailCodeHeader($params, $context);
}

/**
 * Custom mailjet transactional bounce hook
 * @param $bounce_message
 *
 */
function donrec_civicrm_mailjet_transactional_bounce($bounce_message) {
  $message = json_decode($bounce_message, TRUE);
  if (isset($message['Payload'])) {
    if ($message['event'] != 'bounce') {
      CRM_Core_Error::debug_log_message("Event isn't a bounce Event, but {$message['event']}. We can't handle this event here. Message: {$bounce_message}");
      return;
    }
    $payload = json_decode($message['Payload'], TRUE);
    if (!isset($payload['contact_id']) || !isset($payload['contribution_id']) || !isset($payload['timestamp']) || !isset($payload['profile_id'])) {
      CRM_Core_Error::debug_log_message("Couldn't parse Bounce information for Event {$bounce_message}");
      return;
    }
    //    parse bounce parameters here
    $result = civicrm_api3('DonationReceipt', 'handlebounce', [
      'contact_id' => $payload['contact_id'],
      'contribution_id' => $payload['contribution_id'],
      'timestamp' => $payload['timestamp'],
      'profile_id' => $payload['profile_id'],
    ]);
  }
}

/**
 * Custom mailjet mailing bounce hook
 * @param $bounce_message
 * Currently not needed here - ZWBs will always be sent in a transactional mail
 */
//function donrec_civicrm_mailjet_mailing_bounce($bounce_message) {
//  CRM_Core_Error::debug_log_message("[com.proveg.mods - mailing bounce hook] " . json_encode($bounce_message));
//  $tmp = json_decode($bounce_message, TRUE);
//  if (isset($tmp['Payload'])) {
//    CRM_Core_Error::debug_log_message("Payload: " . json_encode($tmp['Payload']));
//  }
//}



/**
 * Implements hook_civicrm_tabset()
 *
 * Will inject the FastActivity tab
 */
function donrec_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName == 'civicrm/contact/view') {
    if (CRM_Core_Permission::check('view and copy receipts') || CRM_Core_Permission::check('create and withdraw receipts')) {
      $context['contact_id'] ??= NULL;
      $tabs[] = [
        'id' => 'donation_receipts',
        'url' => CRM_Utils_System::url( 'civicrm/donrec/tab',
          "reset=1&snippet=1&force=1&cid={$context['contact_id']}" ),
        'title' => E::ts('Donation receipts'),
        // If contact_id is not provided, this is being called from the ContactLayout extension to retrieve tab info
        'count' => !empty($context['contact_id']) ? CRM_Donrec_Logic_Receipt::getReceiptCountForContact($context['contact_id']) : NULL,
        'icon' => 'crm-i fa-paperclip',
        'weight' => 300,
      ];
    }
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
function donrec_civicrm_navigationMenu(&$menu) {
  _donrec_civix_insert_navigation_menu(
    $menu,
    'Administer/CiviContribute',
    [
      'label' => E::ts('Donation Receipts'),
      'name' => 'donrec',
      'operator' => 'OR',
      'separator' => 0,
      // TODO: Once FontAwesome has been updated, use "fa-receipt".
      'icon' => 'crm-i fa-balance-scale',
    ]
  );
  _donrec_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/donrec', array(
    'label' => E::ts('Donation Receipts Settings'),
    'name' => 'donrec_settings',
    'url' => 'civicrm/admin/setting/donrec',
    // TODO: Adjust permission once there is a separate one.
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-cog',
  ));
  _donrec_civix_insert_navigation_menu($menu, 'Administer/CiviContribute/donrec', array(
    'label' => E::ts('Donation Receipts Profiles'),
    'name' => 'donrec_profiles',
    'url' => 'civicrm/admin/setting/donrec/profiles',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-cogs',
  ));
}

/*
 * return errors if a receipted contribution is going to be changed
 */
 //TODO: the pre-hook need the same informations than this one and is called
 // afterwards. Is it possible to make these informations available for it?
function donrec_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
  // Validate contribution_form for already existing contributions.
  // Therefore we need a contribution-id.
  if ($formName == 'CRM_Contribute_Form_Contribution' && !empty($form->_id)) {
    // Validate the contribution values to be set.
    $errors += CRM_Donrec_Logic_Settings::validateContribution($form->_id, $form->_values, $fields);
  }
}

/*
 * Implements hook_civicrm_pre().
 */
function donrec_civicrm_pre( $op, $objectName, $id, &$params ) {
  // Check for forbidden changes on receipted contributions.
  if ($objectName == 'Contribution' && ($op == 'edit' || $op == 'delete')) {

    $has_item = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($id);
    $in_snapshot = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($id);

    if ($has_item || $in_snapshot) {
      if ($op == 'edit') {
        // Get the contribution.
        $query = "
          SELECT *
          FROM civicrm_contribution
          WHERE id = $id";
        $result = CRM_Core_DAO::executeQuery($query);
        $result->fetch();

        // Validate the contribution values to be set.
        CRM_Donrec_Logic_Settings::validateContribution($id, (array)$result, $params, TRUE);
      } elseif ($op == 'delete') {
        $message = sprintf(E::ts("This contribution [%d] must not be deleted because it has a receipt or is going to be receipted!"), $id);
        throw new Exception($message);
      }
    }
  }
  return;
}

/**
 * Implements hook_civicrm_post().
 */
function donrec_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  // Care for transaction-wrapped calls by deferring to a callback.
  if (CRM_Core_Transaction::isActive()) {
    CRM_Core_Transaction::addCallback(
      CRM_Core_Transaction::PHASE_POST_COMMIT,
      '_donrec_civicrm_post_callback',
      [$op, $objectName, $objectId, $objectRef]
    );
  }
  else {
    _donrec_civicrm_post_callback(
      $op,
      $objectName,
      $objectId,
      $objectRef
    );
  }
}

/**
 * Actual implementation of hook_civicrm_post(), used as a callback in the hook
 * implementation itself to care for transaction-wrapped calls.
 *
 * @see donrec_civicrm_post().
 *
 * @param $op
 * @param $objectName
 * @param $objectId
 * @param $objectRef
 */
function _donrec_civicrm_post_callback($op, $objectName, $objectId, $objectRef) {
  if ($objectName == 'Contribution') {
    switch ($op) {
      case 'create':
        // Clear donrec custom fields, since they might have got values copied
        // from a previous contribution when created through the
        // Contribution.repeattransaction API action, which is especially true
        // for recurring contributions created by payment processors.
        // TODO: Remove this workaround when the issue is fixed in CiviCRM core,
        //   i.e. custom field values are not being copied anymore when creating
        //   contributions through Contribution.repeattransaction.
        //   @link https://github.com/civicrm/civicrm-core/pull/17454
        static $receipt_item_table = NULL;
        if (!isset($receipt_item_table)) {
          $receipt_item_table = CRM_Donrec_DataStructure::getTableName(
            'zwb_donation_receipt_item'
          );
        }
        $query = "
          DELETE FROM {$receipt_item_table}
          WHERE `entity_id` = {$objectId}
          ;";
        CRM_Core_DAO::executeQuery($query);
        break;
    }
  }
}

/**
 * Prune the "find contributions" and "advanced contact search" forms
 * by removing the fields that don't make sense or don't work
 */
function donrec_civicrm_buildForm($formName, &$form) {
  if ($formName=='CRM_Contribute_Form_Search') {
    $item_fields = CRM_Donrec_Logic_ReceiptItem::getCustomFields();

    // remove unwanted fields
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'financial_type_id');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'total_amount');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'non_deductible_amount');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'currency');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'contribution_hash');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_on');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'receive_date');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_in');
    $form->assign('field_ids_to_remove', implode(',', $field_ids_to_remove));
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Donrec/Form/Search/RemoveFields.snippet.tpl'
    ));

    // DISABLED: date field search doesn't work
    // CRM_Utils_DonrecHelper::relabelDateField($form, $item_fields, 'issued_on', E::E::ts("Issed On - From"), ts("Issed On - To"));
    // CRM_Utils_DonrecHelper::relabelDateField($form, $item_fields, 'receive_date', E::E::ts("Received - From"), ts("Received - To"));

    // override the standard fields
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'status');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        E::ts('Status'),
        // TODO: use future status definitions
        array(  ''                => E::ts('- any -'),
                'original'        => E::ts('original'),
                'copy'            => E::ts('copy'),
                'withdrawn'       => E::ts('withdrawn'),
                'withdrawn_copy'  => E::ts('withdrawn_copy'),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'type');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        E::ts('Type'),
        // TODO: use future status definitions
        array(  ''        => E::ts('- any -'),
                'single'  => E::ts('single receipt'),
                'bulk'    => E::ts('bulk receipt'),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_in');
    if ($status_id) $form->add('text', "custom_{$status_id}", E::ts('Receipt ID'));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_by');
    if ($status_id) $form->add('text', "custom_{$status_id}", E::ts('Issued by contact'));




  } elseif ($formName=='CRM_Contact_Form_Search_Advanced') {

    // remove unwanted fields
    $item_fields = CRM_Donrec_Logic_Receipt::getCustomFields();
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_on');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'original_file');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'contact_type');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'gender');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'prefix');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'display_name');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'postal_greeting_display');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'email_greeting_display');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'addressee_display');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'street_address');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'supplemental_address_1');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'supplemental_address_2');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'supplemental_address_3');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'postal_code');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'city');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'country');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_addressee_display');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_street_address');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_supplemental_address_1');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_supplemental_address_2');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_supplemental_address_3');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_postal_code');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_city');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'shipping_country');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'date_from');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'date_to');

    // remove unwanted fields from receipt items (in contribution tab)
    $item_fields_receipt = CRM_Donrec_Logic_ReceiptItem::getCustomFields();
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'financial_type_id');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'total_amount');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'non_deductible_amount');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'currency');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'contribution_hash');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'issued_on');
    $field_ids_to_remove[] = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'receive_date');

    $form->assign('field_ids_to_remove', implode(',', $field_ids_to_remove));
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Donrec/Form/Search/RemoveFields.snippet.tpl'
    ));

    // DISABLED: date field search doesn't work
    //CRM_Utils_DonrecHelper::relabelDateField($form, $item_fields, 'issued_on', E::E::ts("Issed On - From"), ts("Issed On - To"));

    // override the standard fields
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'status');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        E::ts('Status'),
        // TODO: use future status definitions
        array(  ''                => E::ts('- any -'),
                'original'        => E::ts('original'),
                'copy'            => E::ts('copy'),
                'withdrawn'       => E::ts('withdrawn'),
                'withdrawn_copy'  => E::ts('withdrawn_copy'),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'type');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        E::ts('Type'),
        // TODO: use future status definitions
        array(  ''        => E::ts('- any -'),
                'single'  => E::ts('single receipt'),
                'bulk'    => E::ts('bulk receipt'),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_by');
    if ($status_id) $form->add('text', "custom_{$status_id}", E::ts('Issued by contact'));


    // override the receipt_item standard fields
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'status');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        E::ts('Status'),
        // TODO: use future status definitions
        array(  ''                => E::ts('- any -'),
                'original'        => E::ts('original'),
                'copy'            => E::ts('copy'),
                'withdrawn'       => E::ts('withdrawn'),
                'withdrawn_copy'  => E::ts('withdrawn_copy'),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'type');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        E::ts('Type'),
        // TODO: use future status definitions
        array(  ''        => E::ts('- any -'),
                'single'  => E::ts('single receipt'),
                'bulk'    => E::ts('bulk receipt'),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'issued_in');
    if ($status_id) $form->add('text', "custom_{$status_id}", E::ts('Receipt ID'));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'issued_by');
    if ($status_id) $form->add('text', "custom_{$status_id}", E::ts('Issued by contact'));
  }
}

// /**
//  * Implements hook_civicrm_entityTypes().
//  *
//  * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
//  */
// function donrec_civicrm_entityTypes(&$entityTypes) {
//   _donrec_civix_civicrm_entityTypes($entityTypes);
// }
