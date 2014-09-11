<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2014 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)			 |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| TODO: License                                          |
+--------------------------------------------------------*/

/**
 * This is the PDF exporter base class
 */
class CRM_Donrec_Exporters_BasePDF extends CRM_Donrec_Logic_Exporter {
	private $template; 

	public function __construct() {
		$this->template = CRM_Donrec_Logic_Templates::getTemplate(1337);
	}

	public function exportSingle($chunk) {
		$reply = array();
		$smarty = CRM_Core_Smarty::singleton();

		// prepare shared tokens
		$domain = CRM_Core_BAO_Domain::getDomain();
		$domain_tokens = array();
		foreach (array('name', 'address') as $token) {
			$domain_tokens[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain, true, true);
		}

		// assign all shared template variables
		$smarty->assign('organisation', $domain_tokens);

		$success = 0;
		$failures = 0;
		foreach ($chunk as $chunk_id => $chunk_item) {
			//$this->setProcessInformation($chunk_id, array('test' => 'PDF was here!'));

			// prepare unique template variables


			// assign all unique template variables
			$smarty->assign('total', $chunk_item['total_amount']);
			$smarty->assign('totaltext', CRM_Donrec_Logic_Templates::num_to_text($chunk_item['total_amount']));
		
			// compile template
			$html = $this->template->msg_html;
			$html = $smarty->fetch("string:$html");

			// set up file names
			$config = CRM_Core_Config::singleton();
			$filename = CRM_Utils_File::makeFileName(sprintf("donrec_%d.pdf", $chunk_item['id']));
			$filename = sprintf("%s%s", $config->customFileUploadDir, $filename);

			// render PDF receipt
			$written = file_put_contents($filename, CRM_Utils_PDF_Utils::html2pdf($html, null, true, $this->template->pdf_format_id));
			if ($written === FALSE) {
				$failures++;
			}else{
				$success++;
			}
		}

		// add a log entry
		CRM_Donrec_Logic_Exporter::addLogEntry($reply, sprintf('PDF processed %d items - %d succeeded, %d failed', count($chunk), $success, $failures), CRM_Donrec_Logic_Exporter::LOG_TYPE_INFO);
		return $reply;
	}

	public function exportBulk($chunk) {
		
	}

	public function wrapUp($chunk) {
		
	}

	/**
	 * @return the ID of this importer class
	 */
	public function getID() {
		return 'PDF';
	}
}