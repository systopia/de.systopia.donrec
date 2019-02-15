<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres -at- systopia.de)            |
| Author: P.Batroff (batroff -at- systopia.de)           |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * Class CRM_Donrec_Logic_EmailReturnProcessor
 */
class CRM_Donrec_Logic_EmailReturnProcessor {

  public static $ZWB_HEADER_PATTERN = "DONREC#{contact_id}#{receipt_id}#";
  public static $ZWB_HEADER_FIELD   = 'X400-Content-Identifier';
  public static $PROCESSED_FOLDER   = 'INBOX.CiviMail.processed';
  public static $IGNORED_FOLDER     = 'INBOX.CiviMail.ignored';
  public static $FAILED_FOLDER      = 'INBOX.CiviMail.failed';

  protected $params = NULL;

  private $mailbox = NULL;
  private $folder_list = NULL;

  private $result_contact_ids;
  private $matched_message_ids;
  private $unmatched_message_ids;


  /**
   * CRM_Donrec_Logic_EmailReturnProcessor constructor.
   *
   * @param $params array see civicrm_api3_donation_receipt_engine_processreturns
   */
  public function __construct($params) {
    $this->params = $params;

    // connect
    $this->mailbox = $this->create_imap_connection();

    // make sure all our folders are there...
    $this->check_or_create_mailbox_folder(self::$IGNORED_FOLDER);
    $this->check_or_create_mailbox_folder(self::$PROCESSED_FOLDER);
    $this->check_or_create_mailbox_folder(self::$FAILED_FOLDER);
  }


  /**
   * Run Email Processor:
   *   search for Mails with given ZWB string
   *
   * @return array stats on the result
   * @throws \Exception
   */
  public function run() {
    $stats = [
        'limit'     => $this->params['limit'],
        'count'     => 0,
        'processed' => 0,
        'ignored'   => 0,
        'failed'    => 0];

    // prepare the pattern
    if (empty($this->params['pattern'])) {
      $this->params['pattern'] = str_replace('{contact_id}', '(?P<contact_id>[0-9]+)', self::$ZWB_HEADER_PATTERN);
      $this->params['pattern'] = str_replace('{receipt_id}', '(?P<receipt_id>[0-9]+)', $this->params['pattern']);
    }
    $this->params['pattern'] = "|{$this->params['pattern']}|";

    // get all emails
    $all_messages = $this->get_all_mails_from_mailbox();
    if (empty($all_messages)) $all_messages = [];

    foreach ($all_messages as $key => $message_id) {
      if ($stats['count'] >= $this->params['limit']) {
        break;
      } else {
        $stats['count'] += 1;
      }

      try {
        list($contact_id, $receipt_id) = $this->parseMessage($message_id, $this->params['pattern']);
        if ($contact_id && $receipt_id) {
          $success = $this->processMatch($contact_id, $receipt_id);
          if ($success) {
            $stats['processed'] += 1;
            $this->move_message_to_folder($message_id, self::$PROCESSED_FOLDER);
          } else {
            $stats['failed'] += 1;
            $this->move_message_to_folder($message_id, self::$FAILED_FOLDER);
          }

        } else {
          $stats['ignored'] += 1;
          $this->move_message_to_folder($message_id, self::$IGNORED_FOLDER);
        }
      } catch (Exception $ex) {
        CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor failed on message '{$message_id}': " . $ex->getMessage());
        $stats['failed'] += 1;
        $this->move_message_to_folder($message_id, self::$FAILED_FOLDER);
      }
    }

    // close mail
    imap_expunge($this->mailbox);
    imap_close($this->mailbox, CL_EXPUNGE);

    return $stats;
  }

  /**
   * Extract contact_id and receipt_id from the given message
   * @param $message_id string IMAP message ID
   * @param $pattern    string scanner pattern
   * @return array
   */
  protected function parseMessage($message_id, $pattern) {
    // first try to find it in the HEADER
    $message_header = imap_fetchheader($this->mailbox, $message_id, FT_UID);
    preg_match($pattern, $message_header, $match);
    if (!$match) {
      // not found? try BODY instead
      $message_body = imap_body($this->mailbox, $message_id, FT_UID);
      preg_match($pattern, $message_body, $match);
    }

    if ($match) {
      return [$match['contact_id'], $match['receipt_id']];
    } else {
      return [NULL, NULL];
    }
  }

  /**
   * Process a code match, i.e.:
   *  - create an activity if requested
   *  - withdraw receipt if requested
   *
   * @param $contact_id int contact ID
   * @param $receipt_id int receipt (internal) ID
   *
   * @return bool did the processing work?
   */
  protected function processMatch($contact_id, $receipt_id) {
    try {
      // load and verify receipt
      $receipt = CRM_Donrec_Logic_Receipt::get($receipt_id);
      if (!$receipt) {
        CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor ERROR while processing Contact [{$contact_id}] / Receipt [{$receipt_id}]: Receipt doesn't exist");
        return FALSE;
      }

      $tokens = $receipt->getAllTokens();
      if ($tokens['contributor']['id'] != $contact_id) {
        CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor ERROR while processing Contact [{$contact_id}] / Receipt [{$receipt_id}]: Receipt doesn't belong to the contact");
        return FALSE;
      }

      // create activity
      if (!empty($this->params['activity_type_id'])) {
        civicrm_api3('Activity', 'create', [
            'activity_type_id'   => $this->params['activity_type_id'],
            'subject'            => str_replace('{receipt_id}', $tokens['receipt_id'], $this->params['activity_subject']),
            'activity_date_time' => date('YmdHis'), // TODO: use email time?
            'target_id'          => $contact_id,
            'status_id'          => empty($this->params['withdraw']) ? 'Scheduled' : 'Completed',
            'source_contact_id'  => CRM_Donrec_Logic_Settings::getLoggedInContactID(),
            //'assignee_id'        => $assignee_id,
        ]);
      }

      // withdraw the receipt
      if (!empty($this->params['withdraw'])) {
        switch ($tokens['status']) {
          case 'ORIGINAL':
            civicrm_api3('DonationReceipt', 'withdraw', ['rid' => $receipt_id]);
            break;

          case 'WITHDRAWN':
            CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor Receipt [{$receipt_id}] already withdrawn");
            break;

          default:
            CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor Receipt [{$receipt_id}] cannot be withdrawn, status is {$tokens['status']}");
        }
      }
    } catch (Exception $ex) {
      CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor ERROR while processing Contact [{$contact_id}] / Receipt [{$receipt_id}]: " . $ex->getMessage());
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param $message_id int    message ID (FT_UID)
   * @param $folder     string folder name (not full path)
   */
  protected function move_message_to_folder($message_id, $folder) {
    $success = imap_mail_move($this->mailbox, $message_id, $folder, CP_UID);
    if ($success) {
      imap_expunge($this->mailbox);
    } else {
      CRM_Core_Error::debug_log_message("Donrec.ReturnsProcessor MOVE of [{$message_id}] to '{$folder}' FAILED");
    }
  }

  /**
   * Get all mails from the INBOX
   *
   * @todo: somehow respect limit ($this->params['limit'])
   * @return array
   */
  protected function get_all_mails_from_mailbox() {
    imap_reopen($this->mailbox, $this->get_hostname("INBOX"));
    $imap_messages = imap_search ( $this->mailbox, 'ALL', SE_UID);
    return $imap_messages;
  }


  /**
   * Verify that the mailbox folders are present
   *
   * @throws \Exception
   */
  protected function check_or_create_mailbox_folder($folder) {
    $list = $this->get_folder_list();
    if (is_array($list)) {
      $mailbox_name = $this->get_hostname(imap_utf7_encode($folder));
      if (!in_array($mailbox_name, $list)) {
        // create folder
        $this->folder_list = NULL; // reset folder list
        imap_createmailbox($this->mailbox, $this->get_hostname(imap_utf7_encode($folder)));
      }
    } else {
      throw new Exception("Failed to list/create IMAP folders.");
    }
  }

  /**
   * Get a cached list of all folders
   *
   * @return array
   */
  protected function get_folder_list() {
    if ($this->folder_list === NULL) {
      $this->folder_list = imap_list($this->mailbox, $this->get_hostname(), "*");
    }
    return $this->folder_list;
  }


  /**
   * Open the IMAP connection
   *
   * @return resource
   * @throws \Exception
   */
  private function create_imap_connection() {
    $mbox = imap_open($this->get_hostname(), $this->params['username'], $this->params['password'], OP_HALFOPEN);
    if (!$mbox) {
      throw new Exception("Couldn't connect to {$this->params['hostname']}. Error: " . imap_last_error());
    }
    return $mbox;
  }


  /**
   * Get the get an IMAP reference
   *
   * @param string $mailfolder
   * @todo amend for configuration. Needs TLS, SSL and nonencryption option
   *
   * @return string
   */
  private function get_hostname($mailfolder = "") {
    // TODO: Verify hostname? Needs to be host:port
    $host = "{" . $this->params['hostname'] . "/tls/novalidate-cert}{$mailfolder}";
    return $host;
  }
}