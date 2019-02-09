<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2019 SYSTOPIA                       |
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

  private $hostname;
  private $username;
  private $password;
  private $zwb_pattern;
  private $limit;

  private $imap_hostname;
//  static mail folder for parsed/processed mails, according to bounce processing
  private $mail_folders = array('INBOX.CiviMail.ignored', 'INBOX.CiviMail.processed');

  private $mailbox;

  private $result_contact_ids;

  private $matched_message_ids;
  private $unmatched_message_ids;


  /**
   * CRM_Donrec_Logic_EmailReturnProcessor constructor.
   *
   * @param $hostname
   * @param $username
   * @param $password
   * @param $zwb_pattern
   * @param $limit
   */
  public function __construct($hostname, $username, $password, $zwb_pattern, $limit) {
    $this->hostname   = $hostname;
    $this->username   = $username;
    $this->password   = $password;
    $this->zwb_pattern = html_entity_decode($zwb_pattern);
    $this->limit = $limit;
    $this->result_contact_ids = array();

    $this->get_hostname();
  }


  /**
   * Run Email Processor:
   *   search for Mails with given ZWB string
   * @throws \Exception
   */
  public function run() {
    $this->mailbox = $this->create_imap_connection();
    try {
      $this->check_or_create_mailbox_folder();
      $matched_messages = $this->get_all_mails_from_mailbox();
      $this->extract_contact_ids_from_messages($matched_messages);
      $this->move_emails();

    } catch(Exception $e) {
      // Close Connection, then throw Exception out
      imap_close($this->mailbox);
      throw new Exception("Error {$e->getMessage()}");
    }
    imap_close($this->mailbox, CL_EXPUNGE);
    return $this->result_contact_ids;
  }


  /**
   * @param $messages
   */
  private function extract_contact_ids_from_messages($messages) {
//    $pattern = "/{$this->zwb_pattern}/";
    $matches = array();
    $matched_message_ids = array();
    $unmatched_message_ids = array();
    foreach ($messages as $key => $message_id){
      if ($key >= $this->limit) {
        break;
      }

      $message_body = imap_body($this->mailbox, $message_id, FT_UID);
      preg_match($this->zwb_pattern, $message_body, $matches);

      if (isset($matches['contact_id'])) {
        $this->result_contact_ids[] = $matches['contact_id'];
        $matched_message_ids[] = $message_id;
      } else {
        $unmatched_message_ids[] = $message_id;
      }
    }

    $this->matched_message_ids = implode(',', $matched_message_ids);
    $this->unmatched_message_ids = implode(',', $unmatched_message_ids);
  }


  /**
   * move maatched emails to respective folders
   */
  private function move_emails() {
    $this->move_message_to_folder($this->matched_message_ids, 'INBOX.CiviMail.processed');
    $this->move_message_to_folder($this->unmatched_message_ids, 'INBOX.CiviMail.ignored');
    // delete mails marked for deletion. Needed for imap_move
    imap_expunge($this->mailbox);
  }


  /**
   * @param $message_id   (FT_UID)
   * @param $folder         Valid IMAP mailbox, NOT the whole mailbox name
   */
  private function move_message_to_folder($message_id, $folder) {
    $res = imap_mail_move($this->mailbox, $message_id, $folder, CP_UID);
    // move back to inbox. Imap is weird.
//    imap_reopen($this->mailbox, $this->get_hostname("INBOX"));
    print "something;";
  }



  /**
   * Search in Inbox Emails, BODY for provided string
   *
   * Edit: Get ALL Mails, then apply regex pattern to BODY later
   *
   * TODO: respect limit
   *
   * @return array
   */
  private function get_all_mails_from_mailbox() {
    imap_reopen($this->mailbox, $this->get_hostname("INBOX"));
    $imap_messages = imap_search ( $this->mailbox, 'ALL', SE_UID);
    return $imap_messages;
  }


  /**
   * @throws \Exception
   */
  private function check_or_create_mailbox_folder() {
    $list = imap_list($this->mailbox, $this->get_hostname(), "*");
    if (is_array($list)) {
      foreach ($this->mail_folders as $folder) {
        $mailbox_name = $this->get_hostname(imap_utf7_encode($folder));
        if (!in_array($mailbox_name, $list)) {
          imap_createmailbox($this->mailbox, $this->get_hostname(imap_utf7_encode($folder)));
        }
      }
    } else {
      throw new Exception("Failed to list IMAP Mailboxes.");
    }
  }


  /**
   * @return resource
   * @throws \Exception
   */
  private function create_imap_connection() {
    $mbox = imap_open($this->get_hostname(), $this->username, $this->password, OP_HALFOPEN);
    if (!$mbox) {
      throw new Exception("Couldn't connect to {$this->imap_hostname}. Error: " . imap_last_error());
    }
    return $mbox;
  }


  /**
   * TODO: amend for configuration. Needs TLS, SSL and nonencryption option
   * @param string $mailfolder
   *
   * @return string
   */
  private function get_hostname($mailfolder = "") {
    // TODO: Verify hostname? Needs to be host:port
    // Currently static imaps
//    $host = "{" . $this->hostname . "/imap/ssl}{$mailfolder}";
//    debugging systopia server uses TLS
    $host = "{" . $this->hostname . "/tls/novalidate-cert}{$mailfolder}";
    return $host;
  }


}