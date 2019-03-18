<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/*
* Settings metadata file
*/

return array(
  'donrec_default_profile' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_default_profile',
    'type' => 'String',
    'default' => "all",
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Name of the default profile',
  ),
  'donrec_packet_size' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_packet_size',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 5,
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Packet size',
  ),
  'donrec_return_path_email' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_return_path_email',
    'type' => 'String',
    'default' => '',
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Email Return Path',
  ),
  'donrec_bcc_email' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_bcc_email',
    'type' => 'String',
    'default' => '',
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'BCC Email',
  ),
  'donrec_email_template' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_email_template',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 0,
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Email Template',
  ),
  'donrec_email_stashed_settings' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_email_stashed_settings',
    'type' => 'String',
    'default' => 0,
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'If the EmailPDF exporter modifies the email settings temporarily, the old settings will be stored here.',
  ),
  'donrec_pdfinfo_path' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_pdfinfo_path',
    'type' => 'String',
    'default' => "/usr/bin/pdfinfo",
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'pdfinfo binary path',
  ),
  'donrec_profiles' => array(
    'group_name' => 'Donation Receipt Profiles',
    'group' => 'de.systopia',
    'name' => 'donrec_profiles',
    'type' => 'Array',
    'add' => '4.3',
    'default' => 0,
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Stores the DonationReceipt Profiles',
  ),
  'donrec_watermark_preset' => array(
      'group_name' => 'Donation Receipt Watermark',
      'group' => 'de.systopia',
      'name' => 'donrec_watermark_preset',
      'type' => 'String',
      'add' => '4.3',
      'default' => 0,
      'is_domain' => 1,
      'is_contact' => 0,
      'description' => 'Stores the DonationReceipt Watermark Setting',
  ),
 );
