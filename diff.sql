SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

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

CREATE  TABLE IF NOT EXISTS `codendi`.`plugin_forumml_attachment` (
  `id_attachment` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `id_message` INT(10) UNSIGNED NOT NULL ,
  `file_name` TEXT NOT NULL ,
  `file_type` VARCHAR(80) NOT NULL ,
  `file_size` INT(10) UNSIGNED NOT NULL ,
  `file_path` VARCHAR(255) NOT NULL ,
  `content_id` VARCHAR(255) NOT NULL DEFAULT '' ,
  PRIMARY KEY (`id_attachment`) ,
  INDEX `idx_fk_id_message` (`id_message` ASC, `content_id`(10) ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 16
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`plugin_forumml_header` (
  `id_header` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`id_header`) ,
  INDEX `idx_name` (`name`(20) ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 47
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`plugin_forumml_message` (
  `id_message` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `id_list` INT(10) UNSIGNED NOT NULL ,
  `id_parent` INT(10) UNSIGNED NOT NULL ,
  `body` TEXT NULL DEFAULT NULL ,
  `last_thread_update` INT(10) UNSIGNED NOT NULL DEFAULT '0' ,
  `msg_type` VARCHAR(30) NOT NULL DEFAULT '' ,
  `cached_html` MEDIUMTEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`id_message`) ,
  INDEX `idx_fk_id_list` (`id_list` ASC) ,
  INDEX `idx_fk_id_parent` (`id_parent` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 17
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`plugin_forumml_messageheader` (
  `id_message` INT(10) UNSIGNED NOT NULL ,
  `id_header` INT(10) UNSIGNED NOT NULL ,
  `value` TEXT NOT NULL ,
  PRIMARY KEY (`id_message`, `id_header`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`plugin_git` (
  `repository_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `repository_name` VARCHAR(255) NOT NULL ,
  `repository_description` TEXT NULL DEFAULT NULL ,
  `repository_path` VARCHAR(255) NOT NULL ,
  `repository_parent_id` INT(11) NULL DEFAULT NULL ,
  `project_id` INT(11) NOT NULL DEFAULT '0' ,
  `repository_creation_user_id` INT(11) NOT NULL ,
  `repository_creation_date` DATETIME NOT NULL ,
  `repository_deletion_date` DATETIME NOT NULL ,
  `repository_is_initialized` TINYINT(4) NOT NULL DEFAULT '0' ,
  `repository_access` VARCHAR(255) NOT NULL DEFAULT 'private' ,
  `repository_backend_type` VARCHAR(16) NULL DEFAULT 'gitshell' ,
  `repository_events_mailing_prefix` VARCHAR(64) NULL DEFAULT '[SCM]' ,
  `repository_scope` VARCHAR(1) NULL DEFAULT NULL ,
  `repository_namespace` VARCHAR(255) NULL DEFAULT NULL ,
  PRIMARY KEY (`repository_id`) ,
  INDEX `project_id` (`project_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

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

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `group_id` INT(11) NOT NULL ,
  `name` TEXT NULL DEFAULT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  `item_name` TEXT NULL DEFAULT NULL ,
  `allow_copy` INT(11) NOT NULL DEFAULT '0' ,
  `submit_instructions` TEXT NULL DEFAULT NULL ,
  `browse_instructions` TEXT NULL DEFAULT NULL ,
  `status` CHAR(1) NOT NULL DEFAULT 'A' ,
  `deletion_date` INT(11) NULL DEFAULT NULL ,
  `instantiate_for_new_projects` INT(11) NOT NULL DEFAULT '0' ,
  `stop_notification` INT(11) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) ,
  INDEX `idx_fk_group_id` (`group_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 106
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_artifact` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tracker_id` INT(11) NOT NULL ,
  `last_changeset_id` INT(11) NOT NULL ,
  `submitted_by` INT(11) NOT NULL ,
  `submitted_on` INT(11) NOT NULL ,
  `use_artifact_permissions` TINYINT(1) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) ,
  INDEX `idx_tracker_id` (`tracker_id` ASC) ,
  INDEX `idx_my` (`submitted_by` ASC, `tracker_id` ASC, `last_changeset_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_canned_response` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tracker_id` INT(11) NOT NULL ,
  `title` TEXT NOT NULL ,
  `body` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `tracker_id_idx` (`tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `artifact_id` INT(11) NOT NULL ,
  `submitted_by` INT(11) NULL DEFAULT NULL ,
  `submitted_on` INT(11) NOT NULL ,
  `email` VARCHAR(255) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `artifact_idx` (`artifact_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_comment` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `changeset_id` INT(11) NOT NULL ,
  `comment_type_id` INT(11) NULL DEFAULT NULL ,
  `canned_response_id` INT(11) NULL DEFAULT NULL ,
  `parent_id` INT(11) NULL DEFAULT NULL ,
  `submitted_by` INT(11) NULL DEFAULT NULL ,
  `submitted_on` INT(11) NOT NULL ,
  `body` TEXT NOT NULL ,
  `old_artifact_history_id` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `changeset_idx` (`changeset_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `changeset_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `has_changed` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `value_idx` (`changeset_id` ASC, `field_id` ASC) ,
  INDEX `field_idx` (`field_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 27
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_artifactlink` (
  `changeset_value_id` INT(11) NOT NULL ,
  `artifact_id` INT(11) NOT NULL ,
  `keyword` VARCHAR(32) NOT NULL ,
  `group_id` INT(11) NOT NULL ,
  PRIMARY KEY (`changeset_value_id`, `artifact_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_date` (
  `changeset_value_id` INT(11) NOT NULL ,
  `value` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`changeset_value_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_file` (
  `changeset_value_id` INT(11) NOT NULL ,
  `fileinfo_id` INT(11) NOT NULL ,
  PRIMARY KEY (`changeset_value_id`, `fileinfo_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_float` (
  `changeset_value_id` INT(11) NOT NULL ,
  `value` FLOAT(10,4) NULL DEFAULT NULL ,
  PRIMARY KEY (`changeset_value_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_int` (
  `changeset_value_id` INT(11) NOT NULL ,
  `value` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`changeset_value_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_list` (
  `changeset_value_id` INT(11) NOT NULL ,
  `bindvalue_id` INT(11) NOT NULL ,
  PRIMARY KEY (`changeset_value_id`, `bindvalue_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_openlist` (
  `changeset_value_id` INT(11) NOT NULL ,
  `bindvalue_id` INT(11) NULL DEFAULT NULL ,
  `openvalue_id` INT(11) NULL DEFAULT NULL ,
  `insertion_order` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  PRIMARY KEY (`insertion_order`) ,
  UNIQUE INDEX `idx` (`changeset_value_id` ASC, `bindvalue_id` ASC, `openvalue_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_permissionsonartifact` (
  `changeset_value_id` INT(11) NOT NULL ,
  `use_perm` TINYINT(1) NOT NULL ,
  `ugroup_id` INT(11) NOT NULL )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_changeset_value_text` (
  `changeset_value_id` INT(11) NOT NULL ,
  `value` TEXT NOT NULL ,
  PRIMARY KEY (`changeset_value_id`) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `old_id` INT(11) UNSIGNED NULL DEFAULT NULL ,
  `tracker_id` INT(11) UNSIGNED NOT NULL ,
  `parent_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
  `formElement_type` VARCHAR(255) NOT NULL ,
  `name` TEXT NOT NULL ,
  `label` TEXT NOT NULL ,
  `description` TEXT NOT NULL ,
  `use_it` TINYINT(1) NOT NULL ,
  `rank` INT(11) UNSIGNED NOT NULL ,
  `scope` CHAR(1) NOT NULL ,
  `required` TINYINT(1) NULL DEFAULT NULL ,
  `notifications` TINYINT(1) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `idx_fk_old_id` (`old_id` ASC) ,
  INDEX `idx_fk_tracker_id` (`tracker_id` ASC) ,
  INDEX `idx_fk_parent_id` (`parent_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 137
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_date` (
  `field_id` INT(11) NOT NULL ,
  `default_value` INT(11) NULL DEFAULT NULL ,
  `default_value_type` TINYINT(1) NULL DEFAULT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_float` (
  `field_id` INT(11) NOT NULL ,
  `default_value` FLOAT(10,4) NULL DEFAULT NULL ,
  `maxchars` INT(11) NOT NULL ,
  `size` INT(11) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_int` (
  `field_id` INT(11) NOT NULL ,
  `default_value` INT(11) NULL DEFAULT NULL ,
  `maxchars` INT(11) NOT NULL ,
  `size` INT(11) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_list` (
  `field_id` INT(11) NOT NULL ,
  `bind_type` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_list_bind_decorator` (
  `field_id` INT(11) NOT NULL ,
  `value_id` INT(11) NOT NULL ,
  `red` TINYINT(3) UNSIGNED NOT NULL ,
  `green` TINYINT(3) UNSIGNED NOT NULL ,
  `blue` TINYINT(3) UNSIGNED NOT NULL ,
  PRIMARY KEY (`field_id`, `value_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_list_bind_defaultvalue` (
  `field_id` INT(11) NOT NULL ,
  `value_id` INT(11) NOT NULL ,
  PRIMARY KEY (`field_id`, `value_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_list_bind_static` (
  `field_id` INT(11) NOT NULL ,
  `is_rank_alpha` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_list_bind_static_value` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `old_id` INT(11) NULL DEFAULT NULL ,
  `field_id` INT(11) NOT NULL ,
  `label` VARCHAR(255) NOT NULL ,
  `description` TEXT NOT NULL ,
  `rank` INT(11) NOT NULL ,
  `is_hidden` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `field_id_idx` (`field_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 139
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_list_bind_users` (
  `field_id` INT(11) NOT NULL ,
  `value_function` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_msb` (
  `field_id` INT(11) NOT NULL ,
  `size` INT(11) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_openlist` (
  `field_id` INT(11) NOT NULL ,
  `hint` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_openlist_value` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `field_id` INT(11) UNSIGNED NOT NULL ,
  `label` VARCHAR(255) NOT NULL DEFAULT '' ,
  PRIMARY KEY (`id`) ,
  INDEX `idx_search` (`field_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_string` (
  `field_id` INT(11) NOT NULL ,
  `default_value` TEXT NULL DEFAULT NULL ,
  `maxchars` INT(11) NOT NULL ,
  `size` INT(11) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_field_text` (
  `field_id` INT(11) NOT NULL ,
  `default_value` TEXT NULL DEFAULT NULL ,
  `rows` INT(11) NOT NULL ,
  `cols` INT(11) NOT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_fileinfo` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `old_id` INT(11) NULL DEFAULT NULL ,
  `submitted_by` INT(11) NOT NULL ,
  `description` TEXT NULL DEFAULT NULL ,
  `filename` TEXT NOT NULL ,
  `filesize` BIGINT(20) UNSIGNED NOT NULL ,
  `filetype` TEXT NOT NULL ,
  PRIMARY KEY (`id`) ,
  FULLTEXT INDEX `fltxt` (`description` ASC, `filename` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 101
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_global_notification` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tracker_id` INT(11) NOT NULL ,
  `addresses` TEXT NOT NULL ,
  `all_updates` TINYINT(1) NOT NULL ,
  `check_permissions` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `tracker_id` (`tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_notification` (
  `user_id` INT(11) NOT NULL ,
  `tracker_id` INT(11) NOT NULL ,
  `role_id` INT(11) NOT NULL ,
  `event_id` INT(11) NOT NULL ,
  `notify` INT(11) NOT NULL DEFAULT '1' ,
  INDEX `user_id_idx` (`user_id` ASC) ,
  INDEX `tracker_id_idx` (`tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_notification_event` (
  `event_id` INT(11) NOT NULL ,
  `tracker_id` INT(11) NOT NULL ,
  `event_label` VARCHAR(255) NULL DEFAULT NULL ,
  `rank` INT(11) NOT NULL ,
  `short_description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  `description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  INDEX `event_id_idx` (`event_id` ASC) ,
  INDEX `tracker_id_idx` (`tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_notification_event_default` (
  `event_id` INT(11) NOT NULL ,
  `event_label` VARCHAR(255) NULL DEFAULT NULL ,
  `rank` INT(11) NOT NULL ,
  `short_description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  `description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  INDEX `event_id_idx` (`event_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_notification_role` (
  `role_id` INT(11) NOT NULL ,
  `tracker_id` INT(11) NOT NULL ,
  `role_label` VARCHAR(255) NULL DEFAULT NULL ,
  `rank` INT(11) NOT NULL ,
  `short_description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  `description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  INDEX `role_id_idx` (`role_id` ASC) ,
  INDEX `tracker_id_idx` (`tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_notification_role_default` (
  `role_id` INT(11) NOT NULL ,
  `role_label` VARCHAR(255) NULL DEFAULT NULL ,
  `rank` INT(11) NOT NULL ,
  `short_description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  `description_msg` VARCHAR(255) NULL DEFAULT NULL ,
  INDEX `role_id_idx` (`role_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_perm` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tracker_id` INT(11) NOT NULL ,
  `user_id` INT(11) NOT NULL ,
  `perm_level` INT(11) NOT NULL DEFAULT '0' ,
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `unique_user` (`tracker_id` ASC, `user_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `old_id` INT(11) NULL DEFAULT NULL ,
  `project_id` INT(11) NULL DEFAULT NULL ,
  `user_id` INT(11) NULL DEFAULT NULL ,
  `tracker_id` INT(11) NOT NULL ,
  `is_default` TINYINT(1) NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `description` TEXT NOT NULL ,
  `current_renderer_id` INT(11) NOT NULL ,
  `parent_report_id` INT(11) NULL DEFAULT NULL ,
  `is_query_displayed` TINYINT(1) NOT NULL ,
  `updated_by` INT(11) NULL DEFAULT NULL ,
  `updated_at` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `tracker_idx` (`tracker_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 103
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `report_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `rank` INT(11) NOT NULL ,
  `is_advanced` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `report_idx` (`report_id` ASC) ,
  INDEX `report_field_idx` (`report_id` ASC, `field_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 115
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria_alphanum_value` (
  `criteria_id` INT(11) NOT NULL ,
  `value` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`criteria_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria_date_value` (
  `criteria_id` INT(11) NOT NULL ,
  `op` CHAR(1) NULL DEFAULT NULL ,
  `from_date` INT(11) NULL DEFAULT NULL ,
  `to_date` INT(11) NULL DEFAULT NULL ,
  PRIMARY KEY (`criteria_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria_file_value` (
  `criteria_id` INT(11) NOT NULL ,
  `value` VARCHAR(255) NOT NULL ,
  PRIMARY KEY (`criteria_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria_list_value` (
  `criteria_id` INT(11) NOT NULL ,
  `value` INT(11) NOT NULL ,
  PRIMARY KEY (`criteria_id`, `value`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria_openlist_value` (
  `criteria_id` INT(11) NOT NULL ,
  `value` TEXT NOT NULL ,
  PRIMARY KEY (`criteria_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_criteria_permissionsonartifact_value` (
  `criteria_id` INT(11) NOT NULL ,
  `value` INT(11) NOT NULL )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_renderer` (
  `id` INT(11) NOT NULL AUTO_INCREMENT ,
  `old_id` INT(11) NULL DEFAULT NULL ,
  `report_id` INT(11) NOT NULL ,
  `renderer_type` VARCHAR(255) NOT NULL ,
  `name` VARCHAR(255) NOT NULL ,
  `description` TEXT NOT NULL ,
  `rank` INT(11) NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `report_idx` (`report_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 103
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_renderer_table` (
  `renderer_id` INT(11) NOT NULL ,
  `chunksz` MEDIUMINT(9) NOT NULL ,
  `multisort` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`renderer_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_renderer_table_columns` (
  `renderer_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `rank` INT(11) NOT NULL ,
  `width` TINYINT(4) NOT NULL ,
  PRIMARY KEY (`renderer_id`, `field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_renderer_table_functions_aggregates` (
  `renderer_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `aggregate` VARCHAR(10) NOT NULL ,
  PRIMARY KEY (`renderer_id`, `field_id`, `aggregate`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_report_renderer_table_sort` (
  `renderer_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `is_desc` TINYINT(1) NOT NULL ,
  `rank` INT(11) NOT NULL ,
  PRIMARY KEY (`renderer_id`, `field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_rule` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
  `tracker_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
  `source_field_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
  `source_value_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
  `target_field_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' ,
  `rule_type` TINYINT(4) UNSIGNED NOT NULL DEFAULT '0' ,
  `target_value_id` INT(11) UNSIGNED NULL DEFAULT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `tracker_id` (`tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_semantic_contributor` (
  `tracker_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  PRIMARY KEY (`tracker_id`) ,
  INDEX `filed_id_idx` (`field_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_semantic_status` (
  `tracker_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `open_value_id` INT(11) NOT NULL ,
  INDEX `idx` (`tracker_id` ASC, `field_id` ASC, `open_value_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_semantic_title` (
  `tracker_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  PRIMARY KEY (`tracker_id`) ,
  INDEX `filed_id_idx` (`field_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_staticfield_richtext` (
  `field_id` INT(11) NOT NULL ,
  `static_value` TEXT NULL DEFAULT NULL ,
  PRIMARY KEY (`field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_tooltip` (
  `tracker_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `rank` INT(11) NOT NULL ,
  PRIMARY KEY (`tracker_id`, `field_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_watcher` (
  `user_id` INT(11) NOT NULL DEFAULT '0' ,
  `watchee_id` INT(11) NOT NULL DEFAULT '0' ,
  `tracker_id` INT(11) NOT NULL DEFAULT '0' ,
  INDEX `watchee_id_idx` (`watchee_id` ASC, `tracker_id` ASC) ,
  INDEX `user_id_idx` (`user_id` ASC, `tracker_id` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_workflow` (
  `workflow_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `tracker_id` INT(11) NOT NULL ,
  `field_id` INT(11) NOT NULL ,
  `is_used` TINYINT(1) NOT NULL ,
  PRIMARY KEY (`workflow_id`) ,
  INDEX `idx_wf_tracker_id` (`tracker_id` ASC) ,
  INDEX `idx_wf_field_id` (`field_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 3
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

CREATE  TABLE IF NOT EXISTS `codendi`.`tracker_workflow_transition` (
  `transition_id` INT(11) NOT NULL AUTO_INCREMENT ,
  `from_id` INT(11) NULL DEFAULT NULL ,
  `to_id` INT(11) NOT NULL ,
  `workflow_id` INT(11) NOT NULL ,
  PRIMARY KEY (`transition_id`) ,
  INDEX `idx_wf_workflow_id` (`workflow_id` ASC) )
ENGINE = InnoDB
AUTO_INCREMENT = 18
DEFAULT CHARACTER SET = utf8
COLLATE = latin1_swedish_ci;

ALTER TABLE `codendi`.`tracker_workflow_transition_postactions_field_date` CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT  ;

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

DROP TABLE IF EXISTS `codendi`.`plugin_svntodimensions_parameters` ;

DROP TABLE IF EXISTS `codendi`.`plugin_svntodimensions_log` ;

DROP TABLE IF EXISTS `codendi`.`plugin_serverupdate_upgrade` ;

DROP TABLE IF EXISTS `codendi`.`plugin_salome_proxy` ;

DROP TABLE IF EXISTS `codendi`.`plugin_salome_configuration` ;

DROP TABLE IF EXISTS `codendi`.`plugin_salome_activatedplugins` ;

DROP TABLE IF EXISTS `codendi`.`plugin_hudson_widget` ;

DROP TABLE IF EXISTS `codendi`.`plugin_hudson_job` ;

DROP TABLE IF EXISTS `codendi`.`plugin_cvstodimensions_parameters` ;

DROP TABLE IF EXISTS `codendi`.`plugin_cvstodimensions_modules` ;

DROP TABLE IF EXISTS `codendi`.`plugin_cvstodimensions_log` ;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
