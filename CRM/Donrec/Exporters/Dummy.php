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
class CRM_Donrec_Exporters_Dummy extends CRM_Donrec_Logic_Exporter {

	/**
	 * @return the display name
	 */
	static function name() {
		return ts('Dummy Exporter');
	}

	/**
	 * @return a html snippet that defines the options as form elements
	 */
	static function htmlOptions() {
		return '<p>TEST</p>';
	}

	/**
	 * @return the ID of this importer class
	 */
	public function getID() {
		return 'Dummy';
	}


	/**
	 * export this chunk of individual items
	 */
	public function exportSingle($chunk) {
		error_log('dummy:exportSingle');
		usleep(300);
	}

	/**
	 * bulk-export this chunk of items
	 */
	public function exportBulk($chunk) {
		error_log('dummy:exportBulk');
		usleep(300);
	}

	/**
	 * generate the final result
	 */
	public function wrapUp($chunk) {
		error_log('dummy:wrapup');
		usleep(1000);
	}
}