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
 * This class represents the engine for donation receipt runs
 */
class CRM_Donrec_Logic_Engine {
	
	/**
	 * This is the ID for one particular run
	 * It consists of the snapshot_id and a serial number for that snapshot
	 */
	protected $run_id;

	/**
	 * stores the related snapshot object
	 */
	protected $snapshot;

	/**
	 * stores the parameters as given by the user
	 * 
	 * known parameters:
	 *  exporters  array(exporter_classes)
	 *  bulk       1 or 0 - if 1, accumulative (='bulk') donation receipts should be issued
	 *  test       1 or 0 - if 0, the contributions will not actually be marked as 'reciept_issued'
	 */
	protected $parameters;

	/**
	 * start/continue an export run
	 */
	public function nextStep() {

	}
}