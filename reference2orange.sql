
ALTER TABLE `codendi`.`artifact` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_field` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_field_value` CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `artifact_id` `artifact_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_file` CHANGE COLUMN `filesize` `filesize` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `submitted_by` `submitted_by` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_global_notification` CHANGE COLUMN `tracker_id` `tracker_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `all_updates` `all_updates` TINYINT(1) NOT NULL DEFAULT '0'  , CHANGE COLUMN `check_permissions` `check_permissions` TINYINT(1) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_group_list` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_perm` CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`artifact_report` CHANGE COLUMN `is_default` `is_default` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`doc_data` CHANGE COLUMN `rank` `rank` INT(11) NOT NULL DEFAULT '0'  AFTER `filetype` ;

ALTER TABLE `codendi`.`feedback` CHANGE COLUMN `session_hash` `session_hash` VARCHAR(32) NOT NULL  ;

ALTER TABLE `codendi`.`group_type` CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`groups_notif_delegation` 
ADD INDEX `group_id` (`group_id` ASC, `ugroup_id` ASC) ;

ALTER TABLE `codendi`.`layouts_contents` CHANGE COLUMN `owner_type` `owner_type` CHAR(1) NOT NULL DEFAULT 'u'  ;

ALTER TABLE `codendi`.`owner_layouts` CHANGE COLUMN `owner_type` `owner_type` CHAR(1) NOT NULL DEFAULT 'u'  ;

ALTER TABLE `codendi`.`permissions` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`permissions_values` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`plugin_docman_approval` CHANGE COLUMN `table_owner` `table_owner` INT(11) UNSIGNED NOT NULL DEFAULT '0'  
, ADD INDEX `idx_owner` (`table_owner` ASC, `table_id` ASC) 
, DROP INDEX `version_id` ;

ALTER TABLE `codendi`.`plugin_docman_approval_user` CHANGE COLUMN `reviewer_id` `reviewer_id` INT(11) UNSIGNED NOT NULL DEFAULT '0'  
, ADD INDEX `idx_review` (`reviewer_id` ASC, `table_id` ASC) ;

ALTER TABLE `codendi`.`plugin_docman_item` 
DROP INDEX `search` ;

ALTER TABLE `codendi`.`plugin_docman_metadata` DROP COLUMN `default_value` , CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  
, DROP INDEX `idx_group_id` 
, ADD INDEX `idx_group_id` (`group_id` ASC, `use_it` ASC) ;

ALTER TABLE `codendi`.`plugin_docman_metadata_value` CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `item_id` `item_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`plugin_docman_report` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`plugin_docman_report_filter` CHANGE COLUMN `report_id` `report_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`plugin_docman_tokens` CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `token` `token` VARCHAR(32) NOT NULL  ;

ALTER TABLE `codendi`.`plugin_docman_version` 
ADD INDEX `idx_item_id` (`item_id` ASC) ;

ALTER TABLE `codendi`.`priority_plugin_hook` CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `priority` `priority` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`project_metric_weekly_tmp1` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`project_plugin` CHANGE COLUMN `project_id` `project_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`project_weekly_metric` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;

ALTER TABLE `codendi`.`server` CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`service` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`snippet_category` CHANGE COLUMN `category_id` `category_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`snippet_language` CHANGE COLUMN `language_id` `language_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`snippet_license` CHANGE COLUMN `license_id` `license_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`snippet_type` CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`system_event` CHANGE COLUMN `process_date` `process_date` DATETIME NULL DEFAULT NULL  , CHANGE COLUMN `end_date` `end_date` DATETIME NULL DEFAULT NULL  ;

ALTER TABLE `codendi`.`ugroup` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`ugroup_mapping` CHANGE COLUMN `to_group_id` `to_group_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `src_ugroup_id` `src_ugroup_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `dst_ugroup_id` `dst_ugroup_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`ugroup_user` CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`user` CHANGE COLUMN `shell` `shell` VARCHAR(50) NOT NULL DEFAULT '/usr/lib/codendi/bin/cvssh-restricted'  , CHANGE COLUMN `last_pwd_update` `last_pwd_update` INT(11) UNSIGNED NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`user_plugin` CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`widget_rss` CHANGE COLUMN `owner_type` `owner_type` CHAR(1) NOT NULL DEFAULT 'u'  ;

ALTER TABLE `codendi`.`wiki_attachment` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`wiki_attachment_revision` CHANGE COLUMN `attachment_id` `attachment_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `date` `date` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `revision` `revision` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`wiki_link` CHANGE COLUMN `linkfrom` `linkfrom` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `linkto` `linkto` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`wiki_nonempty` CHANGE COLUMN `id` `id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`wiki_page` CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  AFTER `pagedata` ;

ALTER TABLE `codendi`.`wiki_recent` CHANGE COLUMN `id` `id` INT(11) NOT NULL DEFAULT '0'  ;

ALTER TABLE `codendi`.`wiki_version` CHANGE COLUMN `id` `id` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `version` `version` INT(11) NOT NULL DEFAULT '0'  , CHANGE COLUMN `mtime` `mtime` INT(11) NOT NULL DEFAULT '0'  ;

DROP TABLE IF EXISTS `codendi`.`tracker_workflow_transition` ;

DROP TABLE IF EXISTS `codendi`.`tracker_workflow` ;

DROP TABLE IF EXISTS `codendi`.`tracker_watcher` ;

DROP TABLE IF EXISTS `codendi`.`tracker_tooltip` ;

DROP TABLE IF EXISTS `codendi`.`tracker_staticfield_richtext` ;

DROP TABLE IF EXISTS `codendi`.`tracker_semantic_title` ;

DROP TABLE IF EXISTS `codendi`.`tracker_semantic_status` ;

DROP TABLE IF EXISTS `codendi`.`tracker_semantic_contributor` ;

DROP TABLE IF EXISTS `codendi`.`tracker_rule` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_renderer_table_sort` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_renderer_table_functions_aggregates` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_renderer_table_columns` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_renderer_table` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_renderer` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria_permissionsonartifact_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria_openlist_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria_list_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria_file_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria_date_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria_alphanum_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report_criteria` ;

DROP TABLE IF EXISTS `codendi`.`tracker_report` ;

DROP TABLE IF EXISTS `codendi`.`tracker_perm` ;

DROP TABLE IF EXISTS `codendi`.`tracker_notification_role_default` ;

DROP TABLE IF EXISTS `codendi`.`tracker_notification_role` ;

DROP TABLE IF EXISTS `codendi`.`tracker_notification_event_default` ;

DROP TABLE IF EXISTS `codendi`.`tracker_notification_event` ;

DROP TABLE IF EXISTS `codendi`.`tracker_notification` ;

DROP TABLE IF EXISTS `codendi`.`tracker_global_notification` ;

DROP TABLE IF EXISTS `codendi`.`tracker_fileinfo` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_text` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_string` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_openlist_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_openlist` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_msb` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_list_bind_users` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_list_bind_static_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_list_bind_static` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_list_bind_defaultvalue` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_list_bind_decorator` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_list` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_int` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_float` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field_date` ;

DROP TABLE IF EXISTS `codendi`.`tracker_field` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_text` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_permissionsonartifact` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_openlist` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_list` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_int` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_float` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_file` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_date` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value_artifactlink` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_value` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset_comment` ;

DROP TABLE IF EXISTS `codendi`.`tracker_changeset` ;

DROP TABLE IF EXISTS `codendi`.`tracker_canned_response` ;

DROP TABLE IF EXISTS `codendi`.`tracker_artifact` ;

DROP TABLE IF EXISTS `codendi`.`tracker` ;

DROP TABLE IF EXISTS `codendi`.`plugin_git` ;

DROP TABLE IF EXISTS `codendi`.`plugin_forumml_messageheader` ;

DROP TABLE IF EXISTS `codendi`.`plugin_forumml_message` ;

DROP TABLE IF EXISTS `codendi`.`plugin_forumml_header` ;

DROP TABLE IF EXISTS `codendi`.`plugin_forumml_attachment` ;
