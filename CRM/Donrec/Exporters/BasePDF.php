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

		// prepare tokens
		$domain = CRM_Core_BAO_Domain::getDomain();
		$domain_tokens = array();
		foreach (array('name', 'address') as $token) {
			$domain_tokens[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain, true, true);
		}
		$domain_tokens['address'] = str_replace('> <', '>&nbsp;<', $domain_tokens['address']); /* Hack to work around (yet another) bug in dompdf... */
		
		$success = 0;
		$failures = 0;
		foreach ($chunk as $chunk_id => $chunk_item) {
			//$this->setProcessInformation($chunk_id, array('test' => 'PDF was here!'));
		
			// assign all template variables
			$this->template->assign('organisation', $domain_tokens);

			error_log(print_r($this->template, TRUE));
			// compile template
			$html = $this->template->fetch(sprintf("string:%s", $this->template->msg_html));
			// set up file names
			$config = CRM_Core_Config::singleton();
			$filename = CRM_Utils_File::makeFileName(sprintf("donrec_%d.pdf", $chunk_item['id']));
			$filename = sprintf("%s%s", $config->customFileUploadDir, $filename);
			// render PDF receipt
			$written = file_put_contents($filename, $html);//CRM_Utils_PDF_Utils::html2pdf($html, null, true, $this->template->pdf_format_id));
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