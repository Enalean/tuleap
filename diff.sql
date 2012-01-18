ALTER TABLE `codendi`.`artifact` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_field` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_field_value` CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL  , CHANGE COLUMN `artifact_id` `artifact_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_file` CHANGE COLUMN `filesize` `filesize` INT(11) NOT NULL  , CHANGE COLUMN `submitted_by` `submitted_by` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_global_notification` CHANGE COLUMN `tracker_id` `tracker_id` INT(11) NOT NULL  , CHANGE COLUMN `all_updates` `all_updates` TINYINT(1) NOT NULL  , CHANGE COLUMN `check_permissions` `check_permissions` TINYINT(1) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_group_list` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_perm` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`artifact_report` CHANGE COLUMN `is_default` `is_default` INT(1) NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`doc_data` CHANGE COLUMN `rank` `rank` INT(11) NOT NULL DEFAULT '0'  AFTER `doc_group` ;

ALTER TABLE `codendi`.`feedback` CHANGE COLUMN `session_hash` `session_hash` CHAR(32) NOT NULL  ;

ALTER TABLE `codendi`.`group_type` CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`groups_notif_delegation` 
DROP INDEX `group_id` ;

ALTER TABLE `codendi`.`layouts_contents` CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;

ALTER TABLE `codendi`.`owner_layouts` CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;

ALTER TABLE `codendi`.`permissions` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`permissions_values` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`plugin_docman_approval` CHANGE COLUMN `table_owner` `table_owner` INT(11) UNSIGNED NOT NULL  
, ADD UNIQUE INDEX `version_id` (`version_id` ASC) 
, DROP INDEX `idx_owner` ;

ALTER TABLE `codendi`.`plugin_docman_approval_user` CHANGE COLUMN `reviewer_id` `reviewer_id` INT(11) UNSIGNED NOT NULL  
, DROP INDEX `idx_review` ;

ALTER TABLE `codendi`.`plugin_docman_item` 
ADD INDEX `search` (`group_id` ASC, `delete_date` ASC, `obsolescence_date` ASC) ;

ALTER TABLE `codendi`.`plugin_docman_metadata` ADD COLUMN `default_value` TEXT NOT NULL  AFTER `special` , CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  
, DROP INDEX `idx_group_id` 
, ADD INDEX `idx_group_id` (`group_id` ASC) ;

ALTER TABLE `codendi`.`plugin_docman_metadata_value` CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL  , CHANGE COLUMN `item_id` `item_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`plugin_docman_report` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`plugin_docman_report_filter` CHANGE COLUMN `report_id` `report_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`plugin_docman_tokens` CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  , CHANGE COLUMN `token` `token` CHAR(32) NOT NULL  ;

ALTER TABLE `codendi`.`plugin_docman_version` 
DROP INDEX `idx_item_id` ;

ALTER TABLE `codendi`.`priority_plugin_hook` CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  , CHANGE COLUMN `priority` `priority` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`project_metric_weekly_tmp1` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`project_plugin` CHANGE COLUMN `project_id` `project_id` INT(11) NOT NULL  , CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`project_weekly_metric` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`server` CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL  ;

ALTER TABLE `codendi`.`service` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`snippet_category` CHANGE COLUMN `category_id` `category_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`snippet_language` CHANGE COLUMN `language_id` `language_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`snippet_license` CHANGE COLUMN `license_id` `license_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`snippet_type` CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`system_event` CHANGE COLUMN `process_date` `process_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'  , CHANGE COLUMN `end_date` `end_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'  ;

ALTER TABLE `codendi`.`ugroup` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`ugroup_mapping` CHANGE COLUMN `to_group_id` `to_group_id` INT(11) NOT NULL  , CHANGE COLUMN `src_ugroup_id` `src_ugroup_id` INT(11) NOT NULL  , CHANGE COLUMN `dst_ugroup_id` `dst_ugroup_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`ugroup_user` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`user` CHANGE COLUMN `shell` `shell` VARCHAR(50) NOT NULL DEFAULT '/sbin/nologin'  , CHANGE COLUMN `last_pwd_update` `last_pwd_update` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`user_plugin` CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  , CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`widget_rss` CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;

ALTER TABLE `codendi`.`wiki_attachment` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`wiki_attachment_revision` CHANGE COLUMN `attachment_id` `attachment_id` INT(11) NOT NULL  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  , CHANGE COLUMN `date` `date` INT(11) NOT NULL  , CHANGE COLUMN `revision` `revision` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`wiki_link` CHANGE COLUMN `linkfrom` `linkfrom` INT(11) NOT NULL  , CHANGE COLUMN `linkto` `linkto` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`wiki_nonempty` CHANGE COLUMN `id` `id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`wiki_page` CHANGE COLUMN `cached_html` `cached_html` MEDIUMBLOB NULL DEFAULT NULL  AFTER `pagedata` ;

ALTER TABLE `codendi`.`wiki_recent` CHANGE COLUMN `id` `id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`wiki_version` CHANGE COLUMN `id` `id` INT(11) NOT NULL  , CHANGE COLUMN `version` `version` INT(11) NOT NULL  , CHANGE COLUMN `mtime` `mtime` INT(11) NOT NULL  ;

DROP TABLE IF EXISTS `codendi`.`wiki_session` ;

DROP TABLE IF EXISTS `codendi`.`plugin_userlog_request` ;

DROP TABLE IF EXISTS `codendi`.`plugin_hudson_widget` ;

DROP TABLE IF EXISTS `codendi`.`plugin_hudson_job` ;
