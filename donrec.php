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

/**
 * Implementation of hook_civicrm_config
 */
function donrec_civicrm_config(&$config) {
  _donrec_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function donrec_civicrm_xmlMenu(&$files) {
  _donrec_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function donrec_civicrm_install() {
  return _donrec_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function donrec_civicrm_uninstall() {
  return _donrec_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function donrec_civicrm_enable() {
  // create snapshot database tables
  $config = CRM_Core_Config::singleton();
  $sql = file_get_contents(dirname( __FILE__ ) .'/sql/donrec.sql', true);
  CRM_Utils_File::sourceSQLFile($config->dsn, $sql, NULL, true);

  // create/update custom groups
  CRM_Donrec_DataStructure::update();

  // install default template
  CRM_Donrec_Logic_Template::setDefaultTemplate();

  // rename the custom fields according to l10.
  // FIXME: this is a workaround: if you do this before, the table name change,
  //         BUT we should not be working with static table names
  CRM_Donrec_DataStructure::translateCustomGroups();

  return _donrec_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function donrec_civicrm_disable() {
  // delete the snapshot-table
  CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `civicrm_donrec_snapshot`");
  CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `donrec_snapshot`");

  return _donrec_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function donrec_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _donrec_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function donrec_civicrm_managed(&$entities) {
  return _donrec_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 */
function donrec_civicrm_caseTypes(&$caseTypes) {
  _donrec_civix_civicrm_caseTypes($caseTypes);
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
          'title' => ts('Issue donation receipt(s)', array('domain' => 'de.systopia.donrec')),
          'class' => 'CRM_Donrec_Form_Task_DonrecTask',
          'result' => false);
    }
  }

  // add REBOOK task to contribution list
  if ($objectType == 'contribution') {
    if (CRM_Core_Permission::check('create and withdraw receipts')) {
      $tasks[] = array(
          'title' => ts('Issue donation receipt(s)', array('domain' => 'de.systopia.donrec')),
          'class' => 'CRM_Donrec_Form_Task_ContributeTask',
          'result' => false);
    }
    if (CRM_Core_Permission::check('edit contributions')) {
      $tasks[] = array(
          'title'  => ts('Rebook to contact', array('domain' => 'de.systopia.donrec')),
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
    $headers[] = array('name' => ts('Receipted', array('domain' => 'de.systopia.donrec')));

    $receipted_contribution_ids = array();

    // insert new values
    foreach ($values as $id => $value ) {
      $item_id = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($value['contribution_id'], TRUE);
      if($item_id === FALSE) {
        $values[$id]['is_receipted'] = ts('No', array('domain' => 'de.systopia.donrec'));
      }else{
        $receipted_contribution_ids[] = $value['contribution_id'];    // save as receipted for rebook (see below)
        $values[$id]['is_receipted'] = sprintf('<a href="%s">%s</a>', CRM_Utils_System::url(
        'civicrm/contact/view',
        "reset=1&cid={$value['contact_id']}&rid=$item_id&selectedChild=donation_receipts",
        TRUE, NULL, TRUE),
        ts('Yes', array('domain' => 'de.systopia.donrec')));
      }
    }
    // restore last element
    $headers[] = $actionList;


    // ************************************
    // **       ADD REBOOK ACTION        **
    // ************************************
    // only offer rebook only if the user has the correct permissions
    if (CRM_Core_Permission::check('edit contributions')) {
      $contribution_status_complete = (int) CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
      $title = ts('Rebook', array('domain' => 'de.systopia.donrec'));
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
            $values[$rownr]['action'] = str_replace('</span>', $this_action.'</span>', $row['action']);
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
 * Custom permissions
 */
 function donrec_civicrm_permission(&$permissions) {
  $prefix = ts('DonationReceipts', array('domain' => 'de.systopia.donrec')) . ': ';

  $permissions['view and copy receipts'] = $prefix . ts('view and create copies of receipts', array('domain' => 'de.systopia.donrec'));
  $permissions['create and withdraw receipts'] = $prefix . ts('create and withdraw receipts', array('domain' => 'de.systopia.donrec'));
  $permissions['delete receipts'] = $prefix . ts('delete receipts', array('domain' => 'de.systopia.donrec'));
 }

/**
 * Set settings
 */
function donrec_civicrm_alterSettingsFolders(&$metaDataFolders = NULL){
  static $configured = FALSE;
  if ($configured) return;
  $configured = TRUE;

  $extRoot = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $extDir = $extRoot . 'settings';
  if(!in_array($extDir, $metaDataFolders)){
    $metaDataFolders[] = $extDir;
  }
}

function donrec_civicrm_tabs(&$tabs, $contactID) {
  if (CRM_Core_Permission::check('view and copy receipts') || CRM_Core_Permission::check('create and withdraw receipts')) {
    $url = CRM_Utils_System::url( 'civicrm/donrec/tab',
                                  "reset=1&snippet=1&force=1&cid=$contactID" );
    $tabs[] = array( 'id'    => 'donation_receipts',
                     'url'   => $url,
                     'title' => ts('Donation receipts', array('domain' => 'de.systopia.donrec')),
                     'count' => CRM_Donrec_Logic_Receipt::getReceiptCountForContact($contactID),
                     'weight' => 300);
  }
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
    $id = $form->_id;
    $has_item = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($id);
    $in_snapshot = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($id);

    if ($has_item || $in_snapshot) {
      $forbidden = array(
        'financial_type_id',
        'total_amount',
        'receive_date',
        'currency',
        'contribution_status_id',
        'payment_instrument_id'
      );

      // check if forbidden columns are going to be changed
      foreach ($forbidden as $col) {
        if ($form->_values[$col] != $fields[$col]) {

          // we need a special check for dates
          if (strpos($col, 'date')) {
            // this approach does not considers seconds!
            // (some input-formats does not allow the input of seconds at all)
            $new_date = date('d/m/Y H:i', strtotime($fields['receive_date'] . ' ' . $fields['receive_date_time']));
            $old_date = date('d/m/Y H:i', strtotime($form->_values['receive_date']));
            if ($new_date == $old_date) {
              continue;
            }
          }

          // and another one for amounts
          if (strpos($col, 'amount')) {
            $replace_symbols = array('.');
            $new_amount = str_replace($replace_symbols, ',', $form->_values[$col]);
            $old_amount = $fields[$col];
            if ($new_amount == $old_amount) {
              continue;
            }
          }
          $errors[$col] = sprintf(ts("A donation receipt has been issued for this contribution, or is being processed for a receipt right now. You are not allowed to change the value for '%s'.", array('domain' => 'de.systopia.donrec')), ts($col, array('domain' => 'de.systopia.donrec')));
        }
      }
    }
  }
  return;
}

/*
 * die() if a receipted contribution is going to be changed
 */
function donrec_civicrm_pre( $op, $objectName, $id, &$params ) {
  if ($objectName == 'Contribution' && ($op == 'edit' || $op == 'delete')) {

    $has_item = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($id);
    $in_snapshot = CRM_Donrec_Logic_Snapshot::isInOpenSnapshot($id);

    if ($has_item || $in_snapshot) {
      if ($op == 'edit') {
        // columns that must not be changed
        $forbidden = array(
          'financial_type_id',
          'total_amount',
          'receive_date',
          'currency',
          'contribution_status_id',
          'payment_instrument_id'
        );

        // get the contribution
        $query = "
          SELECT *
          FROM civicrm_contribution
          WHERE id = $id";
        $result = CRM_Core_DAO::executeQuery($query);
        $result->fetch();

        // check if forbidden values are going to be changed.
        foreach ($forbidden as $col) {
          if (isset($params[$col]) && $result->$col != $params[$col]) {
            // we need a extra-check for dates (which are not in the same format)
            if (strpos($col, 'date')) {
              if($col == 'receive_date' && substr(preg_replace('/[-: ]/', '', $result->$col), 0, -2) . "00" == $params[$col]) {
                continue;
              }
            }
            $message = sprintf(ts("The column '%s' of this contribution [%d] must not be changed because it has a receipt or is going to be receipted!", array('domain' => 'de.systopia.donrec')), $col, $id);
            CRM_Utils_DonrecHelper::exitWithMessage($message);
          }
        }
      } elseif ($op == 'delete') {
        $message = sprintf(ts("This contribution [%d] must not be deleted because it has a receipt or is going to be receipted!", array('domain' => 'de.systopia.donrec')), $id);
        CRM_Utils_DonrecHelper::exitWithMessage($message);
      }
    }
  }
  return;
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
    // CRM_Utils_DonrecHelper::relabelDateField($form, $item_fields, 'issued_on', ts("Issed On - From", array('domain' => 'de.systopia.donrec')), ts("Issed On - To", array('domain' => 'de.systopia.donrec')));
    // CRM_Utils_DonrecHelper::relabelDateField($form, $item_fields, 'receive_date', ts("Received - From", array('domain' => 'de.systopia.donrec')), ts("Received - To", array('domain' => 'de.systopia.donrec')));

    // override the standard fields
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'status');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        ts('Status', array('domain' => 'de.systopia.donrec')),
        // TODO: use future status definitions
        array(  ''                => ts('- any -', array('domain' => 'de.systopia.donrec')),
                'original'        => ts('original', array('domain' => 'de.systopia.donrec')),
                'copy'            => ts('copy', array('domain' => 'de.systopia.donrec')),
                'withdrawn'       => ts('withdrawn', array('domain' => 'de.systopia.donrec')),
                'withdrawn_copy'  => ts('withdrawn_copy', array('domain' => 'de.systopia.donrec')),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'type');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        ts('Type', array('domain' => 'de.systopia.donrec')),
        // TODO: use future status definitions
        array(  ''        => ts('- any -', array('domain' => 'de.systopia.donrec')),
                'single'  => ts('single receipt', array('domain' => 'de.systopia.donrec')),
                'bulk'    => ts('bulk receipt', array('domain' => 'de.systopia.donrec')),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_in');
    if ($status_id) $form->add('text', "custom_{$status_id}", ts('Receipt ID', array('domain' => 'de.systopia.donrec')));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_by');
    if ($status_id) $form->add('text', "custom_{$status_id}", ts('Issued by contact', array('domain' => 'de.systopia.donrec')));




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
    //CRM_Utils_DonrecHelper::relabelDateField($form, $item_fields, 'issued_on', ts("Issed On - From", array('domain' => 'de.systopia.donrec')), ts("Issed On - To", array('domain' => 'de.systopia.donrec')));

    // override the standard fields
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'status');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        ts('Status', array('domain' => 'de.systopia.donrec')),
        // TODO: use future status definitions
        array(  ''                => ts('- any -', array('domain' => 'de.systopia.donrec')),
                'original'        => ts('original', array('domain' => 'de.systopia.donrec')),
                'copy'            => ts('copy', array('domain' => 'de.systopia.donrec')),
                'withdrawn'       => ts('withdrawn', array('domain' => 'de.systopia.donrec')),
                'withdrawn_copy'  => ts('withdrawn_copy', array('domain' => 'de.systopia.donrec')),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'type');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        ts('Type', array('domain' => 'de.systopia.donrec')),
        // TODO: use future status definitions
        array(  ''        => ts('- any -', array('domain' => 'de.systopia.donrec')),
                'single'  => ts('single receipt', array('domain' => 'de.systopia.donrec')),
                'bulk'    => ts('bulk receipt', array('domain' => 'de.systopia.donrec')),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields, 'issued_by');
    if ($status_id) $form->add('text', "custom_{$status_id}", ts('Issued by contact', array('domain' => 'de.systopia.donrec')));


    // override the receipt_item standard fields
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'status');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        ts('Status', array('domain' => 'de.systopia.donrec')),
        // TODO: use future status definitions
        array(  ''                => ts('- any -', array('domain' => 'de.systopia.donrec')),
                'original'        => ts('original', array('domain' => 'de.systopia.donrec')),
                'copy'            => ts('copy', array('domain' => 'de.systopia.donrec')),
                'withdrawn'       => ts('withdrawn', array('domain' => 'de.systopia.donrec')),
                'withdrawn_copy'  => ts('withdrawn_copy', array('domain' => 'de.systopia.donrec')),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'type');
    if ($status_id) $form->add('select', "custom_{$status_id}",
        ts('Type', array('domain' => 'de.systopia.donrec')),
        // TODO: use future status definitions
        array(  ''        => ts('- any -', array('domain' => 'de.systopia.donrec')),
                'single'  => ts('single receipt', array('domain' => 'de.systopia.donrec')),
                'bulk'    => ts('bulk receipt', array('domain' => 'de.systopia.donrec')),
                ));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'issued_in');
    if ($status_id) $form->add('text', "custom_{$status_id}", ts('Receipt ID', array('domain' => 'de.systopia.donrec')));
    $status_id = CRM_Utils_DonrecHelper::getFieldID($item_fields_receipt, 'issued_by');
    if ($status_id) $form->add('text', "custom_{$status_id}", ts('Issued by contact', array('domain' => 'de.systopia.donrec')));
  }
}
