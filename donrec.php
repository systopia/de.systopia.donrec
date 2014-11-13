<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: B. Endres (endres -at- systopia.de)    |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
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
    $tasks[] = array(
    'title' => ts('Issue donation receipt(s)'),
    'class' => 'CRM_Donrec_Form_Task_DonrecTask',
    'result' => false);
  }

  // add REBOOK task to contribution list
  if ($objectType == 'contribution') {
    if (CRM_Core_Permission::check('administer CiviCRM')) {
      $tasks[] = array(
          'title'  => ts('Rebook to contact'),
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
    $headers[] = array('name' => ts('Receipted'));

    $receipted_contribution_ids = array();

    // insert new values
    foreach ($values as $id => $value ) {
      $item_id = CRM_Donrec_Logic_ReceiptItem::hasValidReceiptItem($value['contribution_id'], TRUE);
      if($item_id === FALSE) {
        $values[$id]['is_receipted'] = ts('No');
      }else{
        $receipted_contribution_ids[] = $value['contribution_id'];    // save as receipted for rebook (see below)
        $values[$id]['is_receipted'] = sprintf('<a href="%s">%s</a>', CRM_Utils_System::url(
        'civicrm/contact/view',
        "reset=1&cid={$value['contact_id']}&rid=$item_id&selectedChild=donation_receipts",
        TRUE, NULL, TRUE),
        ts('Yes'));
      }
    }
    // restore last element
    $headers[] = $actionList;


    // ************************************
    // **       ADD REBOOK ACTION        **
    // ************************************
    $contribution_status_complete = (int) CRM_Core_OptionGroup::getValue('contribution_status', 'Completed', 'name');
    $title = ts('Rebook');
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

/**
 * The extra search column (see above) does not alter the template,
 * so the template itself has to be overwritten to show the generated extra column
 */
function donrec_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  if ($formName == 'CRM_Contribute_Page_Tab') {
    $tplName = 'CRM/Contribute/Page/Tab.donrec.tpl';
  } elseif ($formName == 'CRM_Contribute_Form_Search') {
    $tplName = 'CRM/Contribute/Form/Search.donrec.tpl';
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
  $prefix = ts('DonationReceipts') . ': ';

  $permissions['view and copy receipts'] = $prefix . ts('view and create copies of receipts');
  $permissions['create and withdraw receipts'] = $prefix . ts('create and withdraw receipts');
  $permissions['delete receipts'] = $prefix . ts('delete receipts');
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
    $url = CRM_Utils_System::url( 'civicrm/donrec/tab',
                                  "reset=1&snippet=1&force=1&cid=$contactID" );
    $tabs[] = array( 'id'    => 'donation_receipts',
                     'url'   => $url,
                     'title' => ts('Donation receipts'),
                     'count' => CRM_Donrec_Logic_Receipt::getReceiptCountForContact($contactID),
                     'weight' => 300);
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
          $errors[$col] = sprintf(ts("A donation receipt has been issued for this contribution, or is being processed for a receipt right now. You are not allowed to change the value for '%1'."), ts($col));
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
          if ($result->$col != $params[$col]) {
            // we need a extra-check for dates (which are not in the same format)
            if (strpos($col, 'date')) {
              if($col == 'receive_date' && substr(preg_replace('/[-: ]/', '', $result->$col), 0, -2) . "00" == $params[$col]) {
                continue;
              }
            }
            $message = sprintf(ts("The column '%s' of this contribution [%d] must not be changed because it has a receipt or is going to be receipted!"), $col, $id);
            CRM_Utils_DonrecHelper::exitWithMessage($message);
          }
        }
      } elseif ($op == 'delete') {
        $message = sprintf(ts("This contribution [%d] must not be deleted because it has a receipt or is going to be receipted!"), $id);
        CRM_Utils_DonrecHelper::exitWithMessage($message);
      }
    }
  }
  return;
}
