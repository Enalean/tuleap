-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_field` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_field_value` CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL  , CHANGE COLUMN `artifact_id` `artifact_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_file` CHANGE COLUMN `filesize` `filesize` INT(11) NOT NULL  , CHANGE COLUMN `submitted_by` `submitted_by` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_global_notification` CHANGE COLUMN `tracker_id` `tracker_id` INT(11) NOT NULL  , CHANGE COLUMN `all_updates` `all_updates` TINYINT(1) NOT NULL  , CHANGE COLUMN `check_permissions` `check_permissions` TINYINT(1) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_group_list` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_perm` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`artifact_report` CHANGE COLUMN `is_default` `is_default` INT(1) NULL DEFAULT '0'  ;
-- just a matter of column order
ALTER TABLE `codendi`.`doc_data` CHANGE COLUMN `rank` `rank` INT(11) NOT NULL DEFAULT '0'  AFTER `doc_group` ;
-- At Orange there VARCHAR(32)
ALTER TABLE `codendi`.`feedback` CHANGE COLUMN `session_hash` `session_hash` CHAR(32) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`group_type` CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL  ;
-- At orange : there is INDEX `group_id` (`group_id` ASC, `ugroup_id` ASC) ;
ALTER TABLE `codendi`.`groups_notif_delegation` 
DROP INDEX `group_id` ;
-- At orange there is CHAR(1)
ALTER TABLE `codendi`.`layouts_contents` CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;
-- At orange there is CHAR(1)
ALTER TABLE `codendi`.`owner_layouts` CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`permissions` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`permissions_values` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
-- At Orange there is ADD INDEX `idx_owner` (`table_owner` ASC, `table_id` ASC) 
ALTER TABLE `codendi`.`plugin_docman_approval` CHANGE COLUMN `table_owner` `table_owner` INT(11) UNSIGNED NOT NULL  
, ADD UNIQUE INDEX `version_id` (`version_id` ASC) 
, DROP INDEX `idx_owner` ;
-- At Orange there is ADD INDEX `idx_review` (`reviewer_id` ASC, `table_id` ASC) ;
ALTER TABLE `codendi`.`plugin_docman_approval_user` CHANGE COLUMN `reviewer_id` `reviewer_id` INT(11) UNSIGNED NOT NULL  
, DROP INDEX `idx_review` ;

ALTER TABLE `codendi`.`plugin_docman_item` 
ADD INDEX `search` (`group_id` ASC, `delete_date` ASC, `obsolescence_date` ASC) ;
-- At Orange there is NOT NULL 'default 0'
-- At Orange there is ADD INDEX `idx_group_id` (`group_id` ASC, `use_it` ASC) ; 
-- => do the change, this way the index can be used for queries where the clause doesn't include use_it 
ALTER TABLE `codendi`.`plugin_docman_metadata` ADD COLUMN `default_value` TEXT NOT NULL  AFTER `special` , CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  
, DROP INDEX `idx_group_id` 
, ADD INDEX `idx_group_id` (`group_id` ASC) ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`plugin_docman_metadata_value` CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL  , CHANGE COLUMN `item_id` `item_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`plugin_docman_report` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`plugin_docman_report_filter` CHANGE COLUMN `report_id` `report_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`plugin_docman_tokens` CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  , CHANGE COLUMN `token` `token` CHAR(32) NOT NULL  ;
-- At Orange there is ADD INDEX `idx_item_id` (`item_id` ASC) ;
ALTER TABLE `codendi`.`plugin_docman_version` 
DROP INDEX `idx_item_id` ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`priority_plugin_hook` CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  , CHANGE COLUMN `priority` `priority` INT(11) NOT NULL  ;
-- At Tuleap there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`project_metric_weekly_tmp1` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`project_plugin` CHANGE COLUMN `project_id` `project_id` INT(11) NOT NULL  , CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ;
-- At Tuleap there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`project_weekly_metric` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`server` CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`service` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`snippet_category` CHANGE COLUMN `category_id` `category_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`snippet_language` CHANGE COLUMN `language_id` `language_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`snippet_license` CHANGE COLUMN `license_id` `license_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`snippet_type` CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL  ;
-- At Orange there is default NULL for process_date and end_date
-- => do this, our code depends on 0000-00-00 ...
ALTER TABLE `codendi`.`system_event` CHANGE COLUMN `process_date` `process_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'  , CHANGE COLUMN `end_date` `end_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`ugroup` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`ugroup_mapping` CHANGE COLUMN `to_group_id` `to_group_id` INT(11) NOT NULL  , CHANGE COLUMN `src_ugroup_id` `src_ugroup_id` INT(11) NOT NULL  , CHANGE COLUMN `dst_ugroup_id` `dst_ugroup_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`ugroup_user` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ;
-- At Orange default is '/usr/lib/codendi/bin/cvssh-restricted', and last_pwd_date is UNSIGNED
-- => don't do this??
ALTER TABLE `codendi`.`user` CHANGE COLUMN `shell` `shell` VARCHAR(50) NOT NULL DEFAULT '/sbin/nologin'  , CHANGE COLUMN `last_pwd_update` `last_pwd_update` INT(11) NOT NULL DEFAULT '0'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`user_plugin` CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  , CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ;
-- At Orange it is CHAR(1)
ALTER TABLE `codendi`.`widget_rss` CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_attachment` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_attachment_revision` CHANGE COLUMN `attachment_id` `attachment_id` INT(11) NOT NULL  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  , CHANGE COLUMN `date` `date` INT(11) NOT NULL  , CHANGE COLUMN `revision` `revision` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_link` CHANGE COLUMN `linkfrom` `linkfrom` INT(11) NOT NULL  , CHANGE COLUMN `linkto` `linkto` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_nonempty` CHANGE COLUMN `id` `id` INT(11) NOT NULL  ;
-- At Orange group_id is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_page` CHANGE COLUMN `cached_html` `cached_html` MEDIUMBLOB NULL DEFAULT NULL  AFTER `pagedata` ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_recent` CHANGE COLUMN `id` `id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `codendi`.`wiki_version` CHANGE COLUMN `id` `id` INT(11) NOT NULL  , CHANGE COLUMN `version` `version` INT(11) NOT NULL  , CHANGE COLUMN `mtime` `mtime` INT(11) NOT NULL  ;
-- => do this, we likely have no code that uses this
DROP TABLE IF EXISTS `codendi`.`wiki_session` ;
