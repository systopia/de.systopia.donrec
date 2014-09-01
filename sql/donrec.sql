--
-- `civicrm_zwb_snapshot`
--

CREATE TABLE IF NOT EXISTS `civicrm_zwb_snapshot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `snapshot_id` int(10) NOT NULL,
  `contribution_id` int(10) unsigned NOT NULL,
  `created_timestamp` datetime NOT NULL,
  `expires_timestamp` datetime NOT NULL,
  `status` int(10) unsigned NOT NULL,
  `created_by` int(10) NOT NULL,
  `total_amount` decimal(20,2) NOT NULL,
  `non_deductible_amount` decimal(20,2) NOT NULL,
  `currency` char(3) COLLATE utf8_unicode_ci NOT NULL,
  `receive_date` datetime NOT NULL,
  PRIMARY KEY (`snapshot_id`,`contribution_id`),
  KEY `id` (`id`),
  KEY `contribution_id` (`contribution_id`),
  KEY `expires_timestamp` (`expires_timestamp`),
  KEY `created_by` (`created_by`),
  KEY `receive_date` (`receive_date`),
  KEY `snapshot_id` (`snapshot_id`),
  CONSTRAINT `FK_donrec_zwb_snapshot_contribution_id` FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

