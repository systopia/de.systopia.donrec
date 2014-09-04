<?php
	require_once 'CRM/Core/Form.php';

	/**
	* Form controller class
	*
	* @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
	*/
	class CRM_Donrec_Form_Task_DonrecTask extends CRM_Contact_Form_Task {
		function preProcess() {
			parent::preProcess();
		}

		function buildQuickForm() {
			$donrecTypes = array(1 => ts('single'), 2 => ts('multi'));
			$this->addRadio('donrec_type', ts('Donation receipt type'), $donrecTypes);
			$this->addDateRange('donrec_contribution_horizon', '_from', '_to', ts('From:'), 'searchDate', FALSE, FALSE);
			$this->add('text', 'donrec_contribution_amount_low', ts('Minimum amount'), array('size' => 8, 'maxlength' => 8));
			$resultFormats = array(1 => ts('DUMMY #1'), 2 => ts('DUMMY #2'));
			$this->addRadio('result_format', ts('Result format'), $resultFormats, NULL, '<br/>');
			$this->addElement('checkbox', 'is_test', ts('Is this a test run?'));   
			$this->addDefaultButtons(ts('Generate donation receipt(s)'));  
			$this->setDefaults(array('donrec_type' => 1, 'result_format' => 1));
		}
		
		function addRules() {
			$this->addRule('donrec_type', ts('Please select a donation receipt type'), 'required');
			$this->addRule('donrec_contribution_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');
			$this->addRule('result_format', ts('Please select a result format'), 'required');
		}

		function postProcess() {
			$values = $this->exportValues();
			$contactIds = $this->_contactIds;

			// TODO: map contact ids to contributions
			$contributionIds = array();

			CRM_Donrec_Logic_Snapshot::create($contributionIds, CRM_Core_Session::getLoggedInContactID());
		}
	}
