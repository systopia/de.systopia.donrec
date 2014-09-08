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
 * This is the base class for all exporters
 */
abstract class CRM_Donrec_Logic_Exporter {

	protected $engine = NULL;

	/**
	 * returns the list of implemented exporters
	 */
	static function listExporters() {
		return array('Dummy', 'PDF');
	}

	/**
	 * init the exporter with the engine object
	 * here, all necessary checks for the exporters 'readyness' should be performed
	 * 
	 * @return NULL if everything is o.k., an error message string if not
	 */
	function init($engine) {
		$this->engine = $engine;

		// TODO: sanity checks
		return NULL;
	}

	/**
	 * @return the ID of this importer class
	 */
	abstract function getID();

	/**
	 * export this chunk of individual items
	 */
	abstract function exportSingle($chunk);

	/**
	 * bulk-export this chunk of items
	 */
	abstract function exportBulk($chunk);

	/**
	 * generate the final result
	 */
	abstract function wrapUp($chunk);
}