--
-- `donrec_snapshot`
--
CREATE TABLE IF NOT EXISTS `donrec_snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_id` int(10) unsigned NOT NULL,
  `profile_id` int(10) unsigned NOT NULL,
  `contribution_id` int(10) unsigned NOT NULL,
  `line_item_id` int(10) unsigned NULL,
  `contact_id` int(10) unsigned NOT NULL,
  `financial_type_id` int(10) unsigned NOT NULL,
  `created_timestamp` datetime NOT NULL,
  `expires_timestamp` datetime NOT NULL,
  `status` char(4) COLLATE utf8_unicode_ci          COMMENT 'NULL, TEST or DONE',
  `process_data` text                               COMMENT 'json data generated while processing, e.g. files created',
  `created_by` int(10) NOT NULL,
  `total_amount` decimal(20,2) NOT NULL,
  `non_deductible_amount` decimal(20,2) DEFAULT NULL,
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `receive_date` datetime NOT NULL,
  `date_from` datetime NOT NULL,
  `date_to` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE `snapshot_contrib_line_item` (`snapshot_id`,`contribution_id`, `line_item_id`),
  KEY `id` (`id`),
  KEY `snapshot_id` (`snapshot_id`),
  KEY `contribution_id` (`contribution_id`),
  KEY `line_item_id` (`line_item_id`),
  KEY `contact_id` (`contact_id`),
  KEY `financial_type_id` (`financial_type_id`),
  KEY `expires_timestamp` (`expires_timestamp`),
  KEY `created_by` (`created_by`),
  KEY `receive_date` (`receive_date`),
  KEY `status` (`status`),
  KEY `date_from` (`date_from`),
  KEY `date_to` (`date_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- removed: CONSTRAINT `FK_donrec_zwb_snapshot_contribution_id` FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution` (`id`)

--
-- Table for storing donrec profiles.
--
CREATE TABLE IF NOT EXISTS `donrec_profile` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `name` char(64) NOT NULL,
    `data` text,
    `variables` text,
    `template` longtext,
    `template_pdf_format_id` int(10) unsigned,
    `is_default` tinyint(4) DEFAULT 0,
    `is_active` tinyint(4) DEFAULT 1,
    `is_locked` tinyint(4) DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
