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
		}
		
		function addRules() {
			
		}

		function postProcess() {
			$values = $this->exportValues();
			parent::postProcess();
		}
	}
