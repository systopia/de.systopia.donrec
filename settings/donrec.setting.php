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
  'donrec_default_template' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_default_template',
    'type' => 'Integer',
    'add' => '4.3',
    'default' => 0,
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'default template to use for receipt-generation',
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
 );
