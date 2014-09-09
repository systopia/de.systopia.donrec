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
 * This is a dummy exporter, for testing purposes
 */
class CRM_Donrec_Exporters_PDF extends CRM_Donrec_Logic_Exporter {

	/**
	 * @return the display name
	 */
	static function name() {
		return ts('Export individual PDFs');
	}

	/**
	 * @return a html snippet that defines the options as form elements
	 */
	static function htmlOptions() {
		return '<br/><i>TEST</i>';
	}

	public function exportSingle($chunk) {
		$reply = array();

		// edit the process information
		foreach ($chunk as $chunk_id => $chunk_item) {
			$this->setProcessInformation($chunk_id, array('test' => 'PDF was here!'));
		}

		usleep(1300);
		
		// add a log entry
		CRM_Donrec_Logic_Exporter::addLogEntry($reply, 'PDF processed ' . count($chunk) . ' items.', CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
		return $reply;
	}

	public function exportBulk($chunk) {
		// TODO: implement
	}

	public function wrapUp($chunk) {
		// TODO: implement
	}

	/**
	 * @return the ID of this importer class
	 */
	public function getID() {
		return 'PDF';
	}
}