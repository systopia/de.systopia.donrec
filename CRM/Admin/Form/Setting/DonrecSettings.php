<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

require_once 'CRM/Admin/Form/Setting.php';
require_once 'CRM/Core/BAO/CustomField.php';
 
class CRM_Admin_Form_Setting_DonrecSettings extends CRM_Admin_Form_Setting
{
	function buildQuickForm( ) {
        CRM_Utils_System::setTitle(ts('Donation Receipts - Settings'));
        $this->addElement('text', 'draft_text', ts('Draft text'));
        $this->addElement('text', 'copy_text', ts('Copy text'));
        $this->addElement('text', 'packet_size', ts('Packet size'));
        $this->addElement('checkbox','store_pdf'); 			 // actually inserted via template
        $this->addElement('checkbox','financial_types_all'); // ...

        $ct = CRM_Donrec_Logic_Settings::getContributionTypes();

        for ($i=1; $i <= count($ct); $i++) { 
        	$this->addElement('checkbox', "financial_types$i");
        }

        $this->addButtons(array(
	      array('type' => 'next', 'name' => ts('Save'), 'isDefault' => TRUE),
	      array('type' => 'cancel', 'name' => ts('Cancel')),
	    ));

	    $this->registerRule('onlypositive', 'callback', 'onlyPositiveIntegers', 'CRM_Admin_Form_Setting_DonrecSettings');
    }

    function addRules() {
    	$this->addRule('draft_text', ts('Draft text can only contain text'), 'lettersonly');
    	$this->addRule('copy_text', ts('Copy text can only contain text'), 'lettersonly');
    	$this->addRule('packet_size', ts('Packet size can only contain positive integers'), 'onlypositive');
  	}

    function preProcess() {
    	$this->assign('financialTypes', CRM_Donrec_Logic_Settings::getContributionTypes());
    	$this->assign('store_pdf', CRM_Donrec_Logic_Settings::saveOriginalPDF());
    	$this->setDefaults(array(
    			'draft_text' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'draft_text'),
    			'copy_text' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'copy_text'),
    			'packet_size' => CRM_Core_BAO_Setting::getItem('Donation Receipt Settings', 'packet_size')
    		));
    }

    function postProcess() {
    	$values = $this->exportValues();

    	// save text fields
    	CRM_Core_BAO_Setting::setItem($values['draft_text'],'Donation Receipt Settings', 'draft_text');
    	CRM_Core_BAO_Setting::setItem($values['copy_text'],'Donation Receipt Settings', 'copy_text');
    	CRM_Core_BAO_Setting::setItem($values['packet_size'],'Donation Receipt Settings', 'packet_size');

    	// save checkboxes
    	CRM_Core_BAO_Setting::setItem(!empty($values['store_pdf']),'Donation Receipt Settings', 'store_original_pdf');
    	$get_all = !empty($values['financial_types_all']);
    	if ($get_all) {
    		CRM_Core_BAO_Setting::setItem('all','Donation Receipt Settings', 'contribution_types');
    	}else{
    		$id_bucket = array();
    		foreach ($values as $key => $value) {
    			if (strpos($key, 'financial_types') === 0) {
    				$id_bucket[] = $value;
    			}
    		}
    		if (count($id_bucket) > 0) {
    			$result = implode(',', $id_bucket);
    			CRM_Core_BAO_Setting::setItem($result,'Donation Receipt Settings', 'contribution_types');
    		}else{
    			// fallback
    			// TODO: maybe there is a better solution
    			CRM_Core_BAO_Setting::setItem('all','Donation Receipt Settings', 'contribution_types');
    		}
    	}

    	$session = CRM_Core_Session::singleton();
        $session->setStatus(ts("Settings successfully saved", ts('Settings'), 'success'));
    	$session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/setting/donrec'));
    }

    static function onlyPositiveIntegers($value) {
	    return !($value <= 0);
  	}

}