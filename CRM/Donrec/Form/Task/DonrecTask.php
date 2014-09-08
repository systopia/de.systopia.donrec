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
			//$resultFormats = array(1 => ts('DUMMY #1'), 2 => ts('DUMMY #2'));
			//$this->addRadio('result_format', ts('Result format'), $resultFormats, NULL, '<br/>');
			//$this->addElement('checkbox', 'is_test', ts('Is this a test run?'));   
			$this->addDefaultButtons(ts('Continue'));  
			$this->setDefaults(array('donrec_type' => 1));
		}
		
		function addRules() {
			$this->addRule('donrec_type', ts('Please select a donation receipt type'), 'required');
			//$this->addRule('result_format', ts('Please select a result format'), 'required');
		}

		function postProcess() {
			$values = $this->exportValues();
			$contactIds = implode(', ', $this->_contactIds);

			// prepare timestamps
			$raw_from_ts = $values['donrec_contribution_horizon_from'];
			$raw_to_ts = $values['donrec_contribution_horizon_to'];
			
			$date_from = $this->convertDate($raw_from_ts);
			$date_to = $this->convertDate($raw_to_ts);

			$query_date_limit = "";
			if ($date_from) {
				$query_date_limit .= "AND UNIX_TIMESTAMP(`receive_date`) >= $date_from";
			}
			if ($date_to) {
				$query_date_limit .= " AND UNIX_TIMESTAMP(`receive_date`) <= $date_to";
			}

			// get table- and column name
			$table_query = "SELECT `cg`.`table_name`,
								   `cf`.`column_name` 
						    FROM `civicrm_custom_group` AS cg,
						         `civicrm_custom_field` AS cf 
						    WHERE `cg`.`name` = 'zwb_donation_receipt_item' 
						    AND `cf`.`custom_group_id` = `cg`.`id` 
						    AND `cf`.`name` = 'status'";

			$results = CRM_Core_DAO::executeQuery($table_query);

			$custom_group_table = NULL;
			$status_column = NULL;
			while ($results->fetch()) {
				$custom_group_table = $results->table_name;
				$status_column = $results->column_name;
			}

			if ($custom_group_table == NULL || $status_column == NULL) {
				// todo: error handling
				// something went wrong here
				error_log("custom_group_table or status_column is empty!");
			}

			// map contact ids to contributions
			$query = "SELECT `civicrm_contribution`.`id` 
					  FROM (`civicrm_contribution`)
					  LEFT JOIN `$custom_group_table` AS b1 ON `civicrm_contribution`.`id` = `b1`.`entity_id` 
					  WHERE `contact_id` IN ($contactIds)
					  $query_date_limit
					  AND (`non_deductible_amount` < `total_amount` OR non_deductible_amount IS NULL)
					  AND `contribution_status_id` = 1
					  AND (`b1`.`id` IS NULL 
					  OR `b1`.`$status_column` NOT IN ('ORIGINAL', 'COPY'))
					  ";
			
			// prepare parameters 
			//$params = array();

			// execute the query
			$result = CRM_Core_DAO::executeQuery($query);//, $params);

			// build array
			$contributionIds = array();
			while ($result->fetch()) {
				$contributionIds[] = $result->id;
			}

			CRM_Donrec_Logic_Snapshot::create($contributionIds, CRM_Core_Session::getLoggedInContactID());
		}

		private function convertDate($raw_date) {
			$date = FALSE;
			if (!empty($raw_date)) {
				$date_object = DateTime::createFromFormat('m/d/Y', $raw_date, new DateTimeZone('Europe/Berlin'));
				if ($date_object) {
					$date = $date_object->getTimestamp();		
				}
			}
			return $date;
		}
	}
