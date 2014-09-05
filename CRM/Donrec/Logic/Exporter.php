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
class CRM_Donrec_Logic_Exporter {

	/**
	 * returns the list of implemented exporters
	 */
	static function listExporters() {
		return array(
			CRM_Donrec_Exporters_Dummy,
			CRM_Donrec_Exporters_PDF);
	}

	function init() {

	}

	abstract function exportSingle();

	abstract function exportBulk();

	abstract function wrapUp();

}