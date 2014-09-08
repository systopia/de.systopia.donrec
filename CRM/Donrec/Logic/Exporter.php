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
	public static function listExporters() {
		return array('Dummy', 'PDF');
	}

	/**
	 * get the class name for the given exporter
	 */
	public static function getClassForExporter($exporter_id) {
		return 'CRM_Donrec_Exporters_' . $exporter_id;
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


	// HELPERS

	/**
	 * get the process information for this exporter type
	 *	for the given snapshot item
	 */
	protected function getProcessInformation($snapshot_item_id) {
		$all_process_information = $this->engine->getSnapshot()->getProcessInformation($snapshot_item_id);
		if (isset($all_process_information[$this->getID()])) {
			return $all_process_information[$this->getID()];
		} else {
			return array();
		}
	}

	/**
	 * set the process information for this exporter type
	 *	for the given snapshot item
	 */
	protected function setProcessInformation($snapshot_item_id, $values) {
		$all_process_information = $this->engine->getSnapshot()->getProcessInformation($snapshot_item_id);
		$all_process_information[$this->getID()] = $values;
		$this->engine->getSnapshot()->setProcessInformation($snapshot_item_id, $all_process_information);
	}
}