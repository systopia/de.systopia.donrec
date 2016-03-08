<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2015 SYSTOPIA                       |
| Author: N.Bochan (bochan -at- systopia.de)             |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/*
* Settings metadata file
*/

return array(
  'default_profile' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'default_profile',
    'type' => 'String',
    'default' => "all",
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Name of the default profile',
  ),
  // 'contribution_types' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'contribution_types',
  //   'type' => 'String',
  //   'default' => "all",
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'Contribution types',
  // ),
  'packet_size' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'packet_size',
    'type' => 'Integer',
    'html_type' => 'Select',
    'default' => 5,
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Packet size',
  ),
  // 'store_original_pdf' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'store_original_pdf',
  //   'type' => 'Integer',
  //   'html_type' => 'Select',
  //   'default' => 0,
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'Store original PDF files',
  // ),
  // 'draft_text' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'draft_text',
  //   'type' => 'String',
  //   'default' => "DRAFT",
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'Draft text',
  // ),
  // 'copy_text' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'copy_text',
  //   'type' => 'String',
  //   'default' => "COPY",
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'Copy text',
  // ),
  'pdfinfo_path' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'pdfinfo_path',
    'type' => 'String',
    'default' => "/usr/bin/pdfinfo",
    'add' => '4.3',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'pdfinfo binary path',
  ),
  'default_template' => array(
    'group_name' => 'Donation Receipt Settings',
    'group' => 'de.systopia',
    'name' => 'default_template',
    'type' => 'Integer',
    'add' => '4.3',
    'default' => 0,
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'default template to use for receipt-generation',
  ),
  // 'legal_address' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'legal_address',
  //   'type' => 'Integer',
  //   'html_type' => 'Select',
  //   'default' => 0, // we use 0 for is_primary
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'legal address',
  // ),
  // 'postal_address' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'postal_address',
  //   'type' => 'Integer',
  //   'html_type' => 'Select',
  //   'default' => 0, // we use 0 for is_primary
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'postal address',
  // ),
  // 'legal_address_fallback' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'legal_address_fallback',
  //   'type' => 'Integer',
  //   'html_type' => 'Select',
  //   'default' => 0, // we use 0 for is_primary
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'fallback for the legal address',
  // ),
  // 'postal_address_fallback' => array(
  //   'group_name' => 'Donation Receipt Settings',
  //   'group' => 'de.systopia',
  //   'name' => 'postal_address_fallback',
  //   'type' => 'Integer',
  //   'html_type' => 'Select',
  //   'default' => 0, // we use 0 for is_primary
  //   'add' => '4.3',
  //   'is_domain' => 1,
  //   'is_contact' => 0,
  //   'description' => 'fallback for the postal address',
  // ),
 );
