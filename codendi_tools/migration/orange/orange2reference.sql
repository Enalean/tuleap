-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact`
    ALTER COLUMN `group_artifact_id` DROP DEFAULT;
-- In Tuleap, default set to ''
ALTER TABLE `artifact_cc`
    ALTER COLUMN `email` SET DEFAULT '';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_field` 
    ALTER COLUMN `group_artifact_id` DROP DEFAULT,
    ALTER COLUMN `field_name` SET DEFAULT '',
    ALTER COLUMN `display_type` SET DEFAULT '',
    ALTER COLUMN `display_size` SET DEFAULT '',
    ALTER COLUMN `label` SET DEFAULT '',
    ALTER COLUMN `scope` SET DEFAULT '';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_field_value` 
    ALTER COLUMN `field_id` DROP DEFAULT, 
    ALTER COLUMN `artifact_id` DROP DEFAULT;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_file` 
    ALTER COLUMN `filesize` DROP DEFAULT, 
    ALTER COLUMN `submitted_by` DROP DEFAULT;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_global_notification` 
    ALTER COLUMN `tracker_id` DROP DEFAULT, 
    ALTER COLUMN `all_updates` DROP DEFAULT, 
    ALTER COLUMN `check_permissions` DROP DEFAULT;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_group_list`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_perm`
    CHANGE COLUMN `group_artifact_id` `group_artifact_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `artifact_report`
    CHANGE COLUMN `is_default` `is_default` INT(1) NULL DEFAULT '0'  ;
ALTER TABLE `cvs_branches`
    ALTER COLUMN  `branch` SET DEFAULT '';
ALTER TABLE `cvs_checkins`
    ALTER COLUMN  `stickytag` SET DEFAULT '';
ALTER TABLE `cvs_dirs`
    ALTER COLUMN  `dir` SET DEFAULT '';
ALTER TABLE `cvs_files`
    ALTER COLUMN  `file` SET DEFAULT '';
ALTER TABLE `cvs_repositories`
    ALTER COLUMN  `repository` SET DEFAULT '';
ALTER TABLE `cvs_tags`
    ALTER COLUMN  `revision` SET DEFAULT '';
-- just a matter of column order
ALTER TABLE `doc_data`
    CHANGE COLUMN `rank` `rank` INT(11) NOT NULL DEFAULT '0'  AFTER `doc_group`,
    ALTER COLUMN `title` SET DEFAULT '' ;
ALTER TABLE `doc_groups`
    ALTER COLUMN `groupname` SET DEFAULT '' ;
-- At Orange there VARCHAR(32)
ALTER TABLE `feedback`
    CHANGE COLUMN `session_hash` `session_hash` CHAR(32) NOT NULL,
    ALTER COLUMN `created_at` DROP DEFAULT ;
ALTER TABLE `filemodule`
    ALTER COLUMN `recent_filerelease` SET DEFAULT '' ;
ALTER TABLE `filerelease`
    ALTER COLUMN `old_filename` SET DEFAULT '' ;
ALTER TABLE `forum_agg_msg_count`
    ALTER COLUMN `group_forum_id` SET DEFAULT '0' ,
    ALTER COLUMN `count` SET DEFAULT '0' ; 
ALTER TABLE `group_cvs_history`
    ALTER COLUMN `user_name` SET DEFAULT '';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `group_type`
    CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL  ;
ALTER TABLE `groups`
    ALTER COLUMN `unix_group_name` SET DEFAULT '';
-- At Orange there is a duplicated index
ALTER TABLE `groups_notif_delegation` DROP KEY group_id;
ALTER TABLE `image`
    ALTER COLUMN `image_type` SET DEFAULT '';
ALTER TABLE `layouts`
    ALTER COLUMN `name` SET DEFAULT '';
-- At orange there is CHAR(1)
ALTER TABLE `layouts_contents`
    ALTER COLUMN `name` SET DEFAULT '',
    CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;
ALTER TABLE `notifications`
    ALTER COLUMN `type` SET DEFAULT '';
-- At orange there is CHAR(1)
ALTER TABLE `owner_layouts`
    CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `permissions`
    CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `permissions_values`
    CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `plugin_docman_approval`
    CHANGE COLUMN `table_owner` `table_owner` INT(11) UNSIGNED NOT NULL;
ALTER TABLE `plugin_docman_approval` DROP KEY idx_owner;
ALTER TABLE `plugin_docman_approval_user` 
    ALTER COLUMN `reviewer_id` DROP DEFAULT,
    DROP KEY `idx_review`;
-- TODO KEY `idx_review` (`reviewer_id`,`table_id`),KEY `idx_review` (`reviewer_id`,`table_id`),
ALTER TABLE `plugin_docman_item`
    ADD INDEX `search` (`group_id`, `delete_date`, `obsolescence_date`);
-- At Orange there is NOT NULL 'default 0'
-- At Orange there is ADD INDEX `idx_group_id` (`group_id` ASC, `use_it` ASC) ; 
-- => do the change, this way the index can be used for queries where the clause doesn't include use_it 
ALTER TABLE `plugin_docman_metadata` 
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL, 
    ALTER COLUMN `name` SET DEFAULT '',
    ALTER COLUMN `label` SET DEFAULT '',
    DROP INDEX `idx_group_id`,
    ADD COLUMN `default_value` text NOT NULL ;
ALTER TABLE `plugin_docman_metadata` 
    ADD INDEX `idx_group_id` (`group_id`);

-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `plugin_docman_metadata_value`
    CHANGE COLUMN `field_id` `field_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `item_id` `item_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `plugin_docman_report`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `plugin_docman_report_filter`
    CHANGE COLUMN `report_id` `report_id` INT(11) NOT NULL  ;
ALTER TABLE `plugin_docman_version` DROP KEY idx_item_id;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `plugin_docman_tokens`
    CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `token` `token` CHAR(32) NOT NULL,
    ALTER COLUMN `created_at` DROP DEFAULT;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `priority_plugin_hook`
    CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `priority` `priority` INT(11) NOT NULL  ;
ALTER TABLE `project_cc`
    ALTER COLUMN `email` SET DEFAULT '';
ALTER TABLE `project_field`
    ALTER COLUMN `field_name` SET DEFAULT '',
    ALTER COLUMN `display_type` SET DEFAULT '',
    ALTER COLUMN `display_size` SET DEFAULT '',
    ALTER COLUMN `label` SET DEFAULT '',
    ALTER COLUMN `scope` SET DEFAULT '';

ALTER TABLE `project_metric`
    ALTER COLUMN `group_id` SET DEFAULT '0';
ALTER TABLE `project_metric_tmp1`
    ALTER COLUMN `group_id` SET DEFAULT '0';
-- At Tuleap there is NOT NULL 'default 0'
ALTER TABLE `project_metric_weekly_tmp1`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `project_plugin`
    CHANGE COLUMN `project_id` `project_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ;
-- At Tuleap there is NOT NULL 'default 0'
ALTER TABLE `project_weekly_metric`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL DEFAULT '0'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `server`
    CHANGE COLUMN `id` `id` INT(11) UNSIGNED NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `service`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
ALTER TABLE `session`
    ALTER COLUMN `session_hash` SET DEFAULT '',
    ALTER COLUMN `ip_addr` SET DEFAULT '';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `snippet_category`
    ALTER COLUMN `category_name` SET DEFAULT '',
    CHANGE COLUMN `category_id` `category_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `snippet_language`
    ALTER COLUMN `language_name` SET DEFAULT '',
    CHANGE COLUMN `language_id` `language_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `snippet_license`
    ALTER COLUMN `license_name` SET DEFAULT '',
    CHANGE COLUMN `license_id` `license_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `snippet_type`
    ALTER COLUMN `type_name` SET DEFAULT '',
    CHANGE COLUMN `type_id` `type_id` INT(11) NOT NULL  ;
ALTER TABLE `snippet_version`
    ALTER COLUMN `filename` SET DEFAULT '',
    ALTER COLUMN `filesize` SET DEFAULT '',
    ALTER COLUMN `filetype` SET DEFAULT '';
ALTER TABLE `survey_rating_aggregate`
    ALTER COLUMN `type` SET DEFAULT '0',
    ALTER COLUMN `id` SET DEFAULT '0',
    ALTER COLUMN `response` SET DEFAULT '0',
    ALTER COLUMN `count` SET DEFAULT '0';
-- At Orange there is default NULL for process_date and end_date
-- => do this, our code depends on 0000-00-00 ...
ALTER TABLE `svn_commits` DROP KEY `idx_search`;
ALTER TABLE `svn_commits` ADD KEY `idx_search` (`group_id`,`whoid`,`id`);
ALTER TABLE `svn_dirs`
    ALTER COLUMN `dir` SET DEFAULT '';
ALTER TABLE `svn_files`
    ALTER COLUMN `file` SET DEFAULT '';
ALTER TABLE `svn_repositories`
    ALTER COLUMN `repository` SET DEFAULT '';
ALTER TABLE `system_event`
    CHANGE COLUMN `process_date` `process_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    CHANGE COLUMN `end_date` `end_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    ALTER COLUMN `create_date` SET DEFAULT '0000-00-00 00:00:00';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `ugroup`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `ugroup_mapping`
    CHANGE COLUMN `to_group_id` `to_group_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `src_ugroup_id` `src_ugroup_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `dst_ugroup_id` `dst_ugroup_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `ugroup_user`
    CHANGE COLUMN `ugroup_id` `ugroup_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ;
-- At Orange last_pwd_date is UNSIGNED
ALTER TABLE `user`
    CHANGE COLUMN `last_pwd_update` `last_pwd_update` INT(11) NOT NULL DEFAULT '0',
    ALTER COLUMN `user_pw` SET DEFAULT '',
    ALTER COLUMN `unix_pw` SET DEFAULT '',
    ALTER COLUMN `realname` SET DEFAULT '';
ALTER TABLE `user_group`
    ALTER COLUMN `admin_flags` SET DEFAULT '';
ALTER TABLE `user_preferences`
    ALTER COLUMN `preference_name` SET DEFAULT '';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `user_plugin`
    CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `plugin_id` `plugin_id` INT(11) NOT NULL  ;
-- At Orange it is CHAR(1)
ALTER TABLE `widget_rss`
    CHANGE COLUMN `owner_type` `owner_type` VARCHAR(1) NOT NULL DEFAULT 'u'  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `wiki_attachment`
    CHANGE COLUMN `group_id` `group_id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `wiki_attachment_revision`
    CHANGE COLUMN `attachment_id` `attachment_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `user_id` `user_id` INT(11) NOT NULL  ,
    CHANGE COLUMN `date` `date` INT(11) NOT NULL  ,
    CHANGE COLUMN `revision` `revision` INT(11) NOT NULL  ;
ALTER TABLE `wiki_group_list`
    ALTER COLUMN `wiki_link` SET DEFAULT '',
    ALTER COLUMN `description` SET DEFAULT '',
    ALTER COLUMN `wiki_name` SET DEFAULT '';
ALTER TABLE `wiki_log`
    ALTER COLUMN `pagename` SET DEFAULT '';
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `wiki_link`
    CHANGE COLUMN `linkfrom` `linkfrom` INT(11) NOT NULL  ,
    CHANGE COLUMN `linkto` `linkto` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `wiki_nonempty`
    CHANGE COLUMN `id` `id` INT(11) NOT NULL  ;
-- At Orange group_id is NOT NULL 'default 0'
ALTER TABLE `wiki_page` MODIFY COLUMN `cached_html` MEDIUMBLOB AFTER `pagedata` ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `wiki_recent`
    CHANGE COLUMN `id` `id` INT(11) NOT NULL  ;
-- At Orange there is NOT NULL 'default 0'
ALTER TABLE `wiki_version`
    CHANGE COLUMN `id` `id` INT(11) NOT NULL  ,
    CHANGE COLUMN `version` `version` INT(11) NOT NULL  ,
    CHANGE COLUMN `mtime` `mtime` INT(11) NOT NULL  ;
-- => do this???, we likely have no code that uses this
-- DROP TABLE IF EXISTS `wiki_session` ;
