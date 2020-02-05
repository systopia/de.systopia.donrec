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
  'donrec_pdfunite_path' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'donrec_pdfunite_path',
    'type' => 'String',
    'default' => "/usr/bin/pdfunite",
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'pdfunite binary path',
  ),
 );
