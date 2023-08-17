#
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

#
# Purpose:
#    Create all the Tuleap tables. (The Database must be created first by hand)
#
# MySQL dump 8.22
#
# Host: localhost    Database: tuleap
#-------------------------------------------------------
# Server version	3.23.51-log
CREATE TABLE tuleap_installed_version
(
    version VARCHAR(254) NULL
);

#
# Table structure for table 'filedownload_log'
#

CREATE TABLE filedownload_log (
  user_id int(11) NOT NULL default '0',
  filerelease_id int(11) NOT NULL default '0',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,filerelease_id),
  KEY time_idx (time),
  KEY filerelease_id_idx (filerelease_id)
);

#
# Table structure for table 'filemodule'
#

CREATE TABLE filemodule (
  filemodule_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  module_name varchar(40) default NULL,
  recent_filerelease varchar(20) NOT NULL default '',
  PRIMARY KEY  (filemodule_id),
  KEY idx_filemodule_group_id (group_id)
);

#
# Table structure for table 'filemodule_monitor'
#

CREATE TABLE filemodule_monitor (
  filemodule_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  anonymous TINYINT(1) NOT NULL default '1',
  KEY idx_filemodule_monitor_id (filemodule_id)
);

#
# Table structure for table 'filerelease'
#

CREATE TABLE filerelease (
  filerelease_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  unix_box varchar(20) NOT NULL default 'remission',
  unix_partition int(11) NOT NULL default '0',
  text_notes text,
  text_changes text,
  release_version varchar(20) default NULL,
  filename varchar(80) default NULL,
  filemodule_id int(11) NOT NULL default '0',
  file_type varchar(50) default NULL,
  release_time int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  file_size int(11) default NULL,
  post_time int(11) NOT NULL default '0',
  text_format int(11) NOT NULL default '0',
  downloads_week int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'N',
  old_filename varchar(80) NOT NULL default '',
  PRIMARY KEY  (filerelease_id),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY unix_box_idx (unix_box),
  KEY post_time_idx (post_time),
  KEY idx_release_time (release_time)
);

#
# Table structure for table 'forum'
#

CREATE TABLE forum (
  msg_id int(11) NOT NULL auto_increment,
  group_forum_id int(11) NOT NULL default '0',
  posted_by int(11) NOT NULL default '0',
  subject text NOT NULL,
  body text NOT NULL,
  date int(11) NOT NULL default '0',
  is_followup_to int(11) NOT NULL default '0',
  thread_id int(11) NOT NULL default '0',
  has_followups int(11) default '0',
  PRIMARY KEY  (msg_id),
  KEY idx_forum_group_forum_id (group_forum_id),
  KEY idx_forum_is_followup_to (is_followup_to),
  KEY idx_forum_thread_id (thread_id),
  KEY idx_forum_id_date (group_forum_id,date),
  KEY idx_forum_id_date_followup (group_forum_id,date,is_followup_to),
  KEY idx_forum_thread_date_followup (thread_id,date,is_followup_to)
);

#
# Table structure for table 'forum_group_list'
#

CREATE TABLE forum_group_list (
  group_forum_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  forum_name text NOT NULL,
  is_public int(11) NOT NULL default '0',
  description text,
  PRIMARY KEY  (group_forum_id),
  FULLTEXT (description),
  KEY idx_forum_group_list_group_id (group_id)
);

#
# Table structure for table 'forum_monitored_forums'
#

CREATE TABLE forum_monitored_forums (
  monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY  (monitor_id),
  KEY idx_forum_monitor_thread_id (forum_id),
  KEY idx_forum_monitor_combo_id (forum_id,user_id)
);

#
# Table structure for 'forum_monitored_threads' table
#

CREATE TABLE forum_monitored_threads (
  thread_monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) NOT NULL default '0',
  thread_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY (thread_monitor_id)
);

#
# Table structure for table 'forum_saved_place'
#

CREATE TABLE forum_saved_place (
  saved_place_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  forum_id int(11) NOT NULL default '0',
  save_date int(11) NOT NULL default '0',
  PRIMARY KEY  (saved_place_id)
);

#
# Table structure for table 'forum_thread_id'
#

CREATE TABLE forum_thread_id (
  thread_id int(11) NOT NULL auto_increment,
  PRIMARY KEY  (thread_id)
);

#
# Table structure for table 'frs_dlstats_file_agg'
#

CREATE TABLE frs_dlstats_file_agg (
  file_id int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_dlstats_file_file_id (file_id),
  KEY idx_dlstats_file_day (day),
  KEY idx_dlstats_file_down (downloads)
);

#
# Table structure for table 'frs_dlstats_filetotal_agg'
#

CREATE TABLE frs_dlstats_filetotal_agg (
  file_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_fid (file_id)
);

#
# Table structure for table 'frs_dlstats_group_agg'
#

CREATE TABLE frs_dlstats_group_agg (
  group_id int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY group_id_idx (group_id),
  KEY day_idx (day),
  KEY downloads_idx (downloads)
);

#
# Table structure for table 'frs_dlstats_grouptotal_agg'
#

CREATE TABLE frs_dlstats_grouptotal_agg (
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_gid (group_id)
);

#
# Table structure for table 'frs_file'
#

CREATE TABLE frs_file (
  file_id int(11) NOT NULL auto_increment,
  filename text,
  filepath varchar(255) default NULL,
  release_id int(11) NOT NULL default '0',
  type_id int(11) NOT NULL default '0',
  processor_id int(11) NOT NULL default '0',
  release_time int(11) NOT NULL default '0',
  file_size bigint NOT NULL default '0',
  post_date int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  computed_md5 varchar(32),
  reference_md5 varchar(32),
  user_id int(11),
  comment TEXT NULL,
  PRIMARY KEY  (file_id),
  KEY idx_frs_file_release_id (release_id),
  KEY idx_frs_file_type (type_id),
  KEY idx_frs_file_date (post_date),
  KEY idx_frs_file_processor (processor_id),
  KEY idx_frs_file_name (filename(45))
);

CREATE TABLE frs_file_deleted (
  file_id int(11) NOT NULL,
  filename text,
  filepath varchar(255) default NULL,
  release_id int(11) NOT NULL default '0',
  type_id int(11) NOT NULL default '0',
  processor_id int(11) NOT NULL default '0',
  release_time int(11) NOT NULL default '0',
  file_size bigint NOT NULL default '0',
  post_date int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  computed_md5 varchar(32),
  reference_md5 varchar(32),
  user_id int(11),
  delete_date INT(11) UNSIGNED NULL,
  purge_date INT(11) UNSIGNED NULL,
  PRIMARY KEY  (file_id),
  INDEX idx_delete_date (delete_date),
  INDEX idx_purge_date (purge_date)
);

CREATE TABLE frs_uploaded_links (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  release_id int(11) NOT NULL,
  release_time int(11) UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  link text NOT NULL,
  owner_id int(11) NOT NULL,
  is_deleted BOOLEAN DEFAULT FALSE,
  INDEX release_idx (release_id)
);

#
# Table structure for table 'frs_filetype'
#

CREATE TABLE frs_filetype (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (type_id)
);

#
# Table structure for table 'frs_package'
#
#status_active = 1, status_deleted = 2, status_hidden = 3
CREATE TABLE frs_package (
  package_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  name text,
  status_id int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  approve_license TINYINT(1) NOT NULL default '1',
  PRIMARY KEY  (package_id),
  KEY idx_package_group_id (group_id)
);

CREATE TABLE frs_download_agreement (
    id INT(11) NOT NULL AUTO_INCREMENT,
    project_id int(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    PRIMARY KEY  (id),
    INDEX idx_project_id(project_id, id)
);

CREATE TABLE frs_package_download_agreement (
    package_id INT(11) NOT NULL,
    agreement_id INT(11) NOT NULL,
    PRIMARY KEY (package_id, agreement_id),
    INDEX idx_reverse(agreement_id, package_id)
);

CREATE TABLE frs_download_agreement_default (
    project_id int(11) NOT NULL,
    agreement_id INT(11) NOT NULL,
    PRIMARY KEY (project_id)
);

#
# Table structure for table 'frs_processor'
#

CREATE TABLE frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  `rank` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (processor_id)
);

#
# Table structure for table 'frs_release'
#
#status_active = 1, status_deleted = 2, status_hidden = 3
CREATE TABLE frs_release (
  release_id int(11) NOT NULL auto_increment,
  package_id int(11) NOT NULL default '0',
  name text,
  notes text,
  changes text,
  status_id int(11) NOT NULL default '0',
  preformatted int(11) NOT NULL default '0',
  release_date int(11) NOT NULL default '0',
  released_by int(11) NOT NULL default '0',
  PRIMARY KEY  (release_id),
  KEY idx_frs_release_by (released_by),
  KEY idx_frs_release_date (release_date),
  KEY idx_frs_release_package (package_id)
);

#
# Table structure for table 'frs_log'
#

CREATE TABLE frs_log (
  log_id int(11) NOT NULL auto_increment,
  time int(11) NOT NULL default 0,
  user_id int(11) NOT NULL default 0,
  group_id int(11) NOT NULL default 0,
  item_id int(11) NOT NULL,
  action_id int(11) NOT NULL,
  PRIMARY KEY (log_id),
  KEY idx_frs_log_group_item (group_id, item_id)
);

CREATE TABLE IF NOT EXISTS frs_global_permissions(
    project_id int(11) NOT NULL,
    permission_type VARCHAR(255) NOT NULL,
    ugroup_id int(11),
    INDEX project_id (project_id),
    INDEX permission_type (permission_type(10))
);

#
# Table structure for table 'group_cvs_full_history'
#

CREATE TABLE group_cvs_full_history (
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  cvs_commits int(11) NOT NULL default '0',
  cvs_adds int(11) NOT NULL default '0',
  cvs_checkouts int(11) NOT NULL default '0',
  cvs_browse int(11) NOT NULL default '0',
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (day)
);

#
# Table structure for table 'group_cvs_history'
#

CREATE TABLE group_cvs_history (
  group_id int(11) NOT NULL default '0',
  user_name varchar(80) NOT NULL default '',
  cvs_commits int(11) NOT NULL default '0',
  cvs_commits_wk int(11) NOT NULL default '0',
  cvs_adds int(11) NOT NULL default '0',
  cvs_adds_wk int(11) NOT NULL default '0',
  KEY group_id_idx (group_id),
  KEY user_name_idx (user_name)
);

#
# Table structure for table 'group_svn_full_history'
#

CREATE TABLE group_svn_full_history (
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  svn_commits int(11) NOT NULL default '0',
  svn_adds int(11) NOT NULL default '0',
  svn_deletes int(11) NOT NULL default '0',
  svn_checkouts int(11) NOT NULL default '0',
  svn_access_count int(11) NOT NULL default '0',
  svn_browse int(11) NOT NULL default '0',
  UNIQUE accessid (group_id,user_id,day),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (day)
);

#
# Table structure for table 'group_history'
#

CREATE TABLE group_history (
  group_history_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  date int(11) default NULL,
  PRIMARY KEY  (group_history_id),
  KEY idx_group_history_group_id (group_id)
);

#
# Table structure for table 'group_type'
#

CREATE TABLE group_type (
  type_id int(11) NOT NULL,
  name text NOT NULL,
  PRIMARY KEY  (type_id)
);


#
# Table structure for table 'groups'
#

CREATE TABLE `groups` (
  group_id int(11) NOT NULL auto_increment,
  group_name varchar(255) default NULL,
  access VARCHAR(16) NOT NULL DEFAULT 'private',
  status char(1) NOT NULL default 'A',
  unix_group_name varchar(30) NOT NULL default '',
  unix_box varchar(20) NOT NULL default 'shell1',
  http_domain varchar(80) default NULL,
  short_description varchar(255) default NULL,
  cvs_box varchar(20) NOT NULL default 'cvs1',
  svn_box varchar(20) NOT NULL default 'svn1',
  register_time int(11) NOT NULL default '0',
  rand_hash text,
  type int(11) NOT NULL default '1',
  built_from_template int(11) NOT NULL default '100',
  cvs_tracker               int(11)    NOT NULL default '1',
  cvs_watch_mode            int(11)    NOT NULL default '0',
  cvs_events_mailing_list   text       NOT NULL,
  cvs_events_mailing_header varchar(64) binary DEFAULT NULL,
  cvs_preamble              text       NOT NULL,
  cvs_is_private            TINYINT(1) NOT NULL DEFAULT 0,
  svn_tracker               int(11)    NOT NULL default '1',
  svn_mandatory_ref         TINYINT    NOT NULL default '0',
  svn_can_change_log        TINYINT(1) NOT NULL default '0',
  svn_events_mailing_header varchar(64) binary DEFAULT NULL,
  svn_preamble              text       NOT NULL,
  svn_accessfile_version_id INT(11)    NULL,
  svn_commit_to_tag_denied  TINYINT(1) NOT NULL DEFAULT '0',
  truncated_emails          TINYINT(1) NOT NULL DEFAULT 0,
  icon_codepoint            VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (group_id),
  KEY idx_groups_status (status),
  KEY idx_groups_access (access),
  KEY idx_groups_unix (unix_group_name),
  KEY idx_groups_type (type),
  KEY idx_groups_register_time (register_time),
  UNIQUE project_shortname (unix_group_name)
);

CREATE TABLE project_webhook_url (
  id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name TEXT NOT NULL,
  url TEXT NOT NULL
);

CREATE TABLE project_webhook_log (
  webhook_id INT(11) UNSIGNED,
  created_on INT(11) NOT NULL,
  status TEXT NOT NULL,
  INDEX idx_webhook_id(webhook_id)
);

CREATE TABLE project_template_xml (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    template_name VARCHAR(255)
);

CREATE TABLE svn_accessfile_history (
    id INT(11) AUTO_INCREMENT,
    version_number INT(11) NOT NULL,
    group_id INT(11) NOT NULL,
    content TEXT,
    version_date INT(11),
    PRIMARY KEY(id),
    INDEX idx_svn_accessfile_group_id(group_id)
);

CREATE TABLE svn_immutable_tags (
    group_id INT(11),
    paths TEXT NOT NULL,
    whitelist TEXT NOT NULL,
    PRIMARY KEY(group_id)
);

CREATE TABLE svn_cache_parameter (
    name VARCHAR(255) PRIMARY KEY,
    value VARCHAR(255)
);

#
# Table structure for table 'group_desc'
#

CREATE TABLE group_desc (
  group_desc_id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  desc_required BOOL NOT NULL DEFAULT FALSE,
  desc_name VARCHAR( 255 ) NOT NULL ,
  desc_description text NULL ,
  desc_rank INT( 11 ) NOT NULL DEFAULT '0',
  desc_type ENUM( 'line', 'text' ) NOT NULL DEFAULT 'text',
  PRIMARY KEY (group_desc_id),
  UNIQUE (desc_name)
);

#
# Table structure for table 'group_desc_value'
#
CREATE TABLE group_desc_value (
  desc_value_id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  group_id INT( 11 ) NOT NULL ,
  group_desc_id INT( 11 ) NOT NULL ,
  value text NOT NULL ,
  PRIMARY KEY (desc_value_id),
  INDEX idx (group_id)
);

#
# Table structure for table 'news_bytes'
#

CREATE TABLE news_bytes (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  is_approved int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  forum_id int(11) NOT NULL default '0',
  summary text,
  details text,
  PRIMARY KEY  (id),
  KEY idx_news_bytes_forum (forum_id),
  KEY idx_news_bytes_group (group_id),
  KEY idx_news_bytes_approved (is_approved)
);

#
# Table structure for table 'session'
#

CREATE TABLE session (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  session_hash CHAR(64) NOT NULL,
  ip_addr varchar(45) NOT NULL default '',
  time int(11) NOT NULL default '0',
  user_agent TEXT NOT NULL,
  KEY idx_session_user_id (user_id),
  KEY idx_session_time (time)
) ENGINE=InnoDB;

#
# Table structure for table 'trove_cat'
#

CREATE TABLE trove_cat (
  trove_cat_id int(11) NOT NULL auto_increment,
  version int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  root_parent int(11) NOT NULL default '0',
  shortname varchar(80) default NULL,
  fullname varchar(80) default NULL,
  description varchar(255) default NULL,
  count_subcat int(11) NOT NULL default '0',
  count_subproj int(11) NOT NULL default '0',
  fullpath text NOT NULL,
  fullpath_ids text,
  mandatory TINYINT(1) NOT NULL DEFAULT 0,
  display_during_project_creation TINYINT(1) NOT NULL DEFAULT 0,
  is_project_flag BOOL NOT NULL DEFAULT false,
  nb_max_values INT(11) NOT NULL default '3',
  PRIMARY KEY  (trove_cat_id),
  KEY parent_idx (parent),
  KEY root_parent_idx (root_parent),
  KEY version_idx (version)
);

#
# Table structure for table 'trove_group_link'
#

CREATE TABLE trove_group_link (
  trove_group_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) NOT NULL default '0',
  trove_cat_version int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  trove_cat_root int(11) NOT NULL default '0',
  PRIMARY KEY  (trove_group_id),
  KEY idx_trove_group_link_group_id (group_id),
  KEY idx_trove_group_link_cat_id (trove_cat_id)
);

#
# Table structure for table 'user'
#

CREATE TABLE user (
  user_id int(11) NOT NULL auto_increment,
  user_name text NOT NULL,
  email text NOT NULL,
  password varchar(255) DEFAULT NULL,
  realname text NOT NULL,
  register_purpose text,
  status char(1) NOT NULL default 'A',
  shell varchar(50) NOT NULL default '/sbin/nologin',
  unix_pw varchar(255) NOT NULL default 'no_passwd',
  unix_status char(1) NOT NULL default 'N',
  unix_uid int(11) NOT NULL default '0',
  unix_box varchar(10) NOT NULL default 'shell1',
  ldap_id text,
  add_date int(11) NOT NULL default '0',
  approved_by int(11) NOT NULL default '0',
  confirm_hash varchar(32) default NULL,
  mail_siteupdates int(11) NOT NULL default '0',
  mail_va int(11) NOT NULL default '0',
  sticky_login int(11) NOT NULL default '0',
  authorized_keys text,
  email_new text,
  timezone varchar(64) default 'GMT',
  language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US',
  last_pwd_update int(11) NOT NULL default '0',
  expiry_date int(11),
  has_custom_avatar TINYINT(1) NOT NULL DEFAULT 0,
  is_first_timer BOOL NOT NULL DEFAULT false,
  passwordless_only BOOL NOT NULL DEFAULT false,
  PRIMARY KEY  (user_id),
  INDEX idx_user_name(user_name(10)),
  INDEX idx_user_mail(email(10)),
  INDEX idx_ldap_id(ldap_id(10)),
  KEY idx_user_user (status)
);


#
# Table structure for table 'user_access'
#

CREATE TABLE user_access (
  user_id int(11) NOT NULL DEFAULT "0",
  last_access_date int(11) NOT NULL DEFAULT 0,
  prev_auth_success INT(11) NOT NULL DEFAULT 0,
  last_auth_success INT(11) NOT NULL DEFAULT 0,
  last_auth_failure INT(11) NOT NULL DEFAULT 0,
  nb_auth_failure INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY  (user_id)
);

CREATE TABLE user_lost_password (
  id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  verifier VARCHAR(255) NOT NULL,
  creation_date INTEGER UNSIGNED NOT NULL,
  INDEX idx_user_id (user_id)
);

CREATE TABLE user_access_key (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  verifier VARCHAR(255) NOT NULL,
  creation_date INT(11) UNSIGNED NOT NULL,
  expiration_date INT(11) UNSIGNED DEFAULT NULL,
  description TEXT,
  last_usage INT(11) UNSIGNED DEFAULT NULL,
  last_ip VARCHAR(45) DEFAULT NULL,
  INDEX idx_expiration_date (expiration_date)
);

CREATE TABLE user_access_key_scope (
  access_key_id INT(11) NOT NULL,
  scope_key VARCHAR(255) NOT NULL,
  PRIMARY KEY (access_key_id, scope_key)
);

#
# Table structure for table 'user_bookmarks'
#

CREATE TABLE user_bookmarks (
  bookmark_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  bookmark_url text,
  bookmark_title text,
  PRIMARY KEY  (bookmark_id),
  KEY idx_user_bookmark_user_id (user_id)
);


#
# Table structure for table 'user_group'
#

CREATE TABLE user_group (
  user_group_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  admin_flags char(16) NOT NULL default '',
  bug_flags int(11) NOT NULL default '0',
  forum_flags int(11) NOT NULL default '0',
  project_flags int(11) NOT NULL default '2',
  patch_flags int(11) NOT NULL default '1',
  support_flags int(11) NOT NULL default '1',
  file_flags int(11) NOT NULL default '0',
  wiki_flags int(11) NOT NULL default '0',
  svn_flags int(11) NOT NULL default '0',
  news_flags int(11) NOT NULL default '0',
  PRIMARY KEY  (user_group_id),
  KEY idx_user_group_user_id (user_id),
  KEY idx_user_group_group_id (group_id),
  KEY bug_flags_idx (bug_flags),
  KEY forum_flags_idx (forum_flags),
  KEY project_flags_idx (project_flags),
  KEY admin_flags_idx (admin_flags)
) ENGINE=InnoDB;


#
# Table structure for table 'user_preferences'
#

CREATE TABLE user_preferences (
  user_id int(11) NOT NULL default '0',
  preference_name varchar(255) NOT NULL default '',
  preference_value text,
  PRIMARY KEY  (user_id,preference_name),
  INDEX idx_name(preference_name)
);

# CREATE cvs support tables

CREATE TABLE cvs_checkins (
  type enum('Change','Add','Remove'),
  ci_when datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  whoid mediumint(9) DEFAULT '0' NOT NULL,
  repositoryid mediumint(9) DEFAULT '0' NOT NULL,
  dirid mediumint(9) DEFAULT '0' NOT NULL,
  fileid mediumint(9) DEFAULT '0' NOT NULL,
  revision varchar(32) binary ,
  stickytag varchar(255) binary DEFAULT '' NOT NULL,
  branchid mediumint(9) DEFAULT '0' NOT NULL,
  addedlines int(11) DEFAULT '999' NOT NULL,
  removedlines int(11) DEFAULT '999' NOT NULL,
  commitid int(11) DEFAULT '0' NOT NULL,
  descid int(11) DEFAULT '0' NOT NULL,
  UNIQUE repositoryid (repositoryid,dirid,fileid,revision),
  KEY ci_when (ci_when),
  KEY repositoryid_2 (repositoryid),
  KEY dirid (dirid),
  KEY fileid (fileid),
  KEY branchid (branchid),
  KEY commitid (commitid)
);

CREATE TABLE cvs_commits (
  id mediumint(9) NOT NULL auto_increment,
  comm_when timestamp,
  whoid mediumint(9) DEFAULT '0' NOT NULL,
  KEY whoid (whoid),
  PRIMARY KEY (id)
);

CREATE TABLE cvs_descs (
  id mediumint(9) NOT NULL auto_increment,
  description text,
  hash bigint(20) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY hash (hash)
);

CREATE TABLE cvs_dirs (
  id mediumint(9) NOT NULL auto_increment,
  dir varchar(128) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE dir (dir)
);

CREATE TABLE cvs_files (
  id mediumint(9) NOT NULL auto_increment,
  file varchar(128) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE file (file)
);

CREATE TABLE cvs_repositories (
  id mediumint(9) NOT NULL auto_increment,
  repository varchar(64) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE repository (repository)
);

CREATE TABLE cvs_tags (
  repositoryid mediumint(9) DEFAULT '0' NOT NULL,
  branchid mediumint(9) DEFAULT '0' NOT NULL,
  dirid mediumint(9) DEFAULT '0' NOT NULL,
  fileid mediumint(9) DEFAULT '0' NOT NULL,
  revision varchar(32) binary DEFAULT '' NOT NULL,
  KEY repositoryid_2 (repositoryid),
  KEY dirid (dirid),
  KEY fileid (fileid),
  KEY branchid (branchid)
);

CREATE TABLE cvs_branches (
  id mediumint(9) NOT NULL auto_increment,
  branch varchar(64) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE branch (branch)
);

# CREATE SVN support tables
# There can be two (commitid,dirid,fileid) triplets with the same values
# e.g. when there is a delete and an add in the same commit.
CREATE TABLE svn_checkins (
  id int(11) NOT NULL auto_increment,
  type enum('Change','Add','Delete'),
  commitid int(11) DEFAULT '0' NOT NULL,
  dirid int(11) DEFAULT '0' NOT NULL,
  fileid int(11) DEFAULT '0' NOT NULL,
  addedlines int(11) DEFAULT '999' NOT NULL,
  removedlines int(11) DEFAULT '999' NOT NULL,
  PRIMARY KEY (id),
  KEY checkins_idx (commitid,dirid,fileid),
  KEY dirid (dirid),
  KEY fileid (fileid)
);

CREATE TABLE svn_commits (
  id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  repositoryid int(11) DEFAULT '0' NOT NULL,
  revision int(11) DEFAULT '0' NOT NULL,
  date int(11) NOT NULL default '0',
  whoid int(11) DEFAULT '0' NOT NULL,
  description text,
  PRIMARY KEY (id),
  UNIQUE uniq_commits_idx (repositoryid,revision),
  KEY whoid (whoid),
  KEY revision (revision),
  KEY idx_search (group_id, whoid, id),
  KEY idx_repositoryid_date (repositoryid, date),
  INDEX idx_date (date),
  INDEX reverse_rev (group_id, revision),
  FULLTEXT (description)
);

CREATE TABLE svn_dirs (
  id int(11) NOT NULL auto_increment,
  dir varchar(255) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_dir_idx (dir)
);

CREATE TABLE svn_files (
  id int(11) NOT NULL auto_increment,
  file varchar(255) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_file_idx (file)
);

CREATE TABLE svn_repositories (
  id int(11) NOT NULL auto_increment,
  repository varchar(255) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE uniq_repository_idx (repository)
);

CREATE TABLE svn_token (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  token VARCHAR(255) NOT NULL,
  generated_date INT(11) UNSIGNED NOT NULL,
  last_usage INT(11) UNSIGNED,
  last_ip VARCHAR(45),
  comment TEXT,
  INDEX idx_user_id (user_id)
);

#
# Service table
#
CREATE TABLE service (
    service_id int(11) NOT NULL auto_increment,
    group_id int(11) NOT NULL,
    label text,
    description text,
    short_name text,
    link text,
    is_active int(11) DEFAULT 0 NOT NULL,
    is_used int(11) DEFAULT 0 NOT NULL,
    scope text NOT NULL,
    `rank` int(11) NOT NULL default '0',
    location ENUM( 'master', 'same', 'satellite' ) NOT NULL DEFAULT 'master', -- distributed architecture: to be deleted (but requires to check all plugins)
    server_id INT( 11 ) UNSIGNED NULL,  -- distributed architecture: to be deleted (but requires to check all plugins)
    is_in_iframe TINYINT(1) NOT NULL DEFAULT '0',
    is_in_new_tab BOOL NOT NULL DEFAULT false,
    icon VARCHAR(255) NOT NULL DEFAULT '',
    primary key (service_id),
    key idx_group_id(group_id),
    INDEX idx_short_name (short_name(10))
);



#
# ugroup table, used to store the description of groups of users (see also ugroup_user table)
#
CREATE TABLE ugroup (
  ugroup_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  source_id INT(11) DEFAULT NULL,
  group_id int(11) NULL,
  PRIMARY KEY (ugroup_id),
  INDEX idx_ugroup_project_id (group_id)
);


#
# ugroup_user table
# Contains the ugroup members (users)
#
CREATE TABLE ugroup_user (
  ugroup_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  PRIMARY KEY (ugroup_id, user_id),
  INDEX idx_user_ugroup(user_id, ugroup_id)
) ENGINE=InnoDB;


#
# permissions table, used to store specific access rights (for packages, releases, documentation, etc.)
#
CREATE TABLE permissions (
  permission_type VARCHAR(255) NOT NULL,
  object_id VARCHAR(255) NOT NULL,
  ugroup_id int(11) NOT NULL,
  INDEX object_id (object_id (10))
);


#
# permissions_values table, used to store the list of default ugroups available by permission_type.
# ugroups are selected from the special ugroups, so their ID should be less than 100.
#
CREATE TABLE permissions_values (
  permission_type text NOT NULL,
  ugroup_id int(11) NOT NULL,
  is_default int(11) NOT NULL default '0'
);




#
# Wiki Service
#

CREATE TABLE wiki_group_list (
	id int(11) NOT NULL auto_increment,
	group_id int(11) NOT NULL default '0',
	wiki_name varchar(255) NOT NULL default '',
	wiki_link varchar(255) NOT NULL default '',
	description varchar(255) NOT NULL default '',
	`rank` int(11) NOT NULL default '0',
        language_id VARCHAR( 17 ) NOT NULL DEFAULT 'en_US',
	PRIMARY KEY (id)
);

# Table for Wiki access logs
CREATE TABLE wiki_log (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  pagename varchar(255) NOT NULL default '',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,group_id),
  KEY time_idx (time),
  KEY group_id_idx (group_id)
);


# Tables for Wiki attachments support
CREATE TABLE wiki_attachment (
  id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  group_id INT( 11 ) NOT NULL ,
  name VARCHAR( 255 ) NOT NULL ,
  filesystem_name VARCHAR( 255 ) DEFAULT NULL,
  delete_date INT(11) UNSIGNED NULL,
  PRIMARY KEY (id)
);

CREATE TABLE wiki_attachment_deleted (
  id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  group_id INT( 11 ) NOT NULL ,
  name VARCHAR( 255 ) NOT NULL ,
  filesystem_name VARCHAR( 255 ) DEFAULT NULL,
  delete_date INT(11) UNSIGNED NULL,
  purge_date INT(11) UNSIGNED NULL,
  PRIMARY KEY (id),
  INDEX idx_delete_date (delete_date),
  INDEX idx_purge_date (purge_date)
);

CREATE TABLE wiki_attachment_revision (
  id INT( 11 ) NOT NULL AUTO_INCREMENT ,
  attachment_id INT( 11 ) NOT NULL ,
  user_id INT( 11 ) NOT NULL ,
  date INT( 11 ) NOT NULL ,
  revision INT( 11 ) NOT NULL ,
  mimetype VARCHAR( 255 ) NOT NULL ,
  size bigint NOT NULL ,
  PRIMARY KEY (id)
);

CREATE TABLE wiki_attachment_log (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  wiki_attachment_id int(11) NOT NULL default '0',
  wiki_attachment_revision_id int(11) NOT NULL default '0',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,group_id),
  KEY time_idx (time),
  KEY group_id_idx (group_id)
);

#
# PHP Wiki tables
#
CREATE TABLE wiki_page (
	id              INT NOT NULL AUTO_INCREMENT,
    pagename        VARCHAR(100) BINARY NOT NULL,
	hits            INT NOT NULL DEFAULT 0,
    pagedata        MEDIUMTEXT NOT NULL,
	cached_html 	MEDIUMBLOB,
	group_id        INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_page_group (group_id,pagename(10))
);

CREATE TABLE wiki_version (
	id              INT NOT NULL,
        version         INT NOT NULL,
	mtime           INT NOT NULL,
	minor_edit      TINYINT DEFAULT 0,
        content         MEDIUMTEXT NOT NULL,
        versiondata     MEDIUMTEXT NOT NULL,
        PRIMARY KEY (id,version),
	INDEX (mtime)
);


CREATE TABLE wiki_recent (
	id              INT NOT NULL,
	latestversion   INT,
	latestmajor     INT,
	latestminor     INT,
        PRIMARY KEY (id)
);


CREATE TABLE wiki_nonempty (
	id              INT NOT NULL,
	PRIMARY KEY (id)
);


CREATE TABLE wiki_link (
	linkfrom        INT NOT NULL,
        linkto          INT NOT NULL,
	INDEX (linkfrom),
        INDEX (linkto)
);

# Plugin tables
# {{{
CREATE TABLE plugin (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  available tinyint(4) NOT NULL default '0',
  prj_restricted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE name (name)
);

CREATE TABLE project_plugin (
  project_id INT NOT NULL ,
  plugin_id INT NOT NULL,
  KEY project_id_idx (project_id),
  KEY plugin_id_idx (plugin_id),
  UNIQUE project_plugin (project_id, plugin_id)
);

# }}}

#
# Table structure for table 'reference'
#
# Notes:
#   - scope='S' means a reference available to all projects
# (defined by site administrators, group_id =100)
#   - scope='P' means a reference available to one project
#
CREATE TABLE reference (
  id int(11) NOT NULL auto_increment,
  keyword varchar(25) NOT NULL,
  description text NOT NULL,
  link text NOT NULL,
  scope char(1) NOT NULL default 'P',
  service_short_name TEXT,
  nature VARCHAR( 64 ) NOT NULL,
  PRIMARY KEY  (id),
  INDEX keyword_idx (keyword),
  INDEX scope_idx (scope)
);

CREATE TABLE reference_group (
  id int(11) NOT NULL auto_increment,
  reference_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  is_active tinyint NOT NULL default '0',
  PRIMARY KEY  (id),
  INDEX group_id_idx (group_id,is_active)
);




CREATE TABLE ugroup_mapping (
  to_group_id int(11) NOT NULL,
  src_ugroup_id int(11) NOT NULL,
  dst_ugroup_id int(11) NOT NULL,
  PRIMARY KEY (to_group_id, src_ugroup_id, dst_ugroup_id)
);

CREATE TABLE feedback (
  session_id INT(11) UNSIGNED,
  feedback TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  PRIMARY KEY (session_id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  user_id int(11) NOT NULL default '0',
  object_id int(11) NOT NULL default '0',
  type varchar(100) NOT NULL default '',
  PRIMARY KEY  (user_id,object_id,type)
);

DROP TABLE IF EXISTS widget_rss;
CREATE TABLE IF NOT EXISTS widget_rss (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  url TEXT NOT NULL,
  KEY (owner_id, owner_type)
);

DROP TABLE IF EXISTS widget_image;
CREATE TABLE IF NOT EXISTS widget_image (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  url TEXT NOT NULL,
  KEY (owner_id, owner_type)
);

DROP TABLE IF EXISTS widget_note;
CREATE TABLE IF NOT EXISTS widget_note (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'g',
  title varchar(255) NOT NULL,
  content TEXT NOT NULL,
  KEY (owner_id, owner_type)
);


#
# cross_references Table
#
DROP TABLE IF EXISTS cross_references;
CREATE TABLE IF NOT EXISTS cross_references (
  id int(11) unsigned NOT NULL AUTO_INCREMENT,
  created_at INT(11) NOT NULL DEFAULT '0',
  user_id INT(11) unsigned NOT NULL DEFAULT '0',
  source_type VARCHAR( 255 ) NOT NULL ,
  source_keyword VARCHAR( 32 ) NOT NULL ,
  source_id VARCHAR( 255 ) NOT NULL DEFAULT '0',
  source_gid INT(11) unsigned NOT NULL DEFAULT '0',
  target_type VARCHAR( 255 ) NOT NULL ,
  target_keyword VARCHAR( 32 ) NOT NULL ,
  target_id VARCHAR( 255 )  NOT NULL DEFAULT '0',
  target_gid INT(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  INDEX source_idx(source_id(10), source_type(10)),
  INDEX target_idx(target_id(10), target_type(10))
);


# --------------------------------------------------------

#
# Table structure for System Events
#
# type        : one of "PROJECT_CREATE", "PROJECT_DELETE", etc.
# parameters  : event parameters (group_id, etc.) depending on event type
# priority    : event priority from 3 (high prio) to 1 (low prio)
# status      : event status: 'NEW' = nothing done yet, 'RUNNING' = event is being processed,
#               'DONE', 'ERROR', 'WARNING' = event processed successfully, with error, or with a warning message respectively.
# create_date : date when the event was created in the DB
# process_date: date when event processing started
# end_date    : date when processing finished
# log         : log message after processing (useful for e.g. error messages or warnings).
DROP TABLE IF EXISTS system_event;
CREATE TABLE IF NOT EXISTS system_event (
  id INT(11) unsigned NOT NULL AUTO_INCREMENT,
  type VARCHAR(255) NOT NULL default '',
  parameters TEXT,
  priority TINYINT(1) NOT NULL default '0',
  status  ENUM( 'NEW', 'RUNNING', 'DONE', 'ERROR', 'WARNING' ) NOT NULL DEFAULT 'NEW',
  create_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  process_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  end_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  owner VARCHAR(255) NOT NULL default 'root',
  log TEXT,
  PRIMARY KEY (id),
  INDEX type_idx (type(20)),
  INDEX idx_status (status)
);

CREATE TABLE system_events_followers (
  id INT(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  emails TEXT NOT NULL ,
  types VARCHAR( 31 ) NOT NULL
);


# --------------------------------------------------------

#
# Table structure for Groups Notif Delegation
#

CREATE TABLE groups_notif_delegation (
  group_id int(11) NOT NULL default 0,
  ugroup_id int(11) NOT NULL,
  PRIMARY KEY (group_id, ugroup_id)
);



# --------------------------------------------------------

#
# Table structure for Message Notif Delegation
#

CREATE TABLE groups_notif_delegation_message (
  group_id int(11) NOT NULL default 0,
  msg_to_requester text NOT NULL,
  PRIMARY KEY (group_id)
);


--
-- Tables for id sharing
--
CREATE TABLE IF NOT EXISTS tracker_idsharing_artifact(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
);
CREATE TABLE IF NOT EXISTS tracker_idsharing_tracker(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
);

# --------------------------------------------------------

#
# Table structure for Svn notification
#

CREATE TABLE IF NOT EXISTS svn_notification (
    group_id int(11) NOT NULL,
    svn_events_mailing_list text NOT NULL,
    path varchar(255) DEFAULT "/",
    PRIMARY KEY (group_id, path)
);

#
# Table structure for Project parent relationship
#

CREATE TABLE IF NOT EXISTS project_parent (
    group_id INT(11) PRIMARY KEY,
    parent_group_id INT(11) NOT NULL
);

#
# Table structure for rest authentication tokens
#

CREATE TABLE IF NOT EXISTS rest_authentication_token (
    token VARCHAR(255) NOT NULL,
    user_id INT(11) NOT NULL,
    created_on INT(11) NOT NULL,
    INDEX idx_rest_authentication_token_token (token(10)),
    INDEX idx_rest_authentication_token_userid (user_id)
);

#
# Table structure for UGroup permissions that are valid for all the forge
#

CREATE TABLE IF NOT EXISTS ugroup_forge_permission (
    ugroup_id INT(11) NOT NULL,
    permission_id INT(11) NOT NULL,
    INDEX idx_user_group_id (ugroup_id)
);


DROP TABLE IF EXISTS  email_gateway_salt;
CREATE TABLE email_gateway_salt (
        salt VARCHAR(255)
    )
;

DROP TABLE IF EXISTS homepage_headline;
CREATE TABLE homepage_headline (
    language_id VARCHAR(17) NOT NULL PRIMARY KEY,
    headline TEXT NOT NULL
);

DROP TABLE IF EXISTS forgeconfig;
CREATE TABLE forgeconfig (
    name VARCHAR(255) PRIMARY KEY,
    value TEXT NOT NULL DEFAULT ''
);

DROP TABLE IF EXISTS password_configuration;
CREATE TABLE password_configuration (
    breached_password_enabled BOOL NOT NULL
);

DROP TABLE IF EXISTS user_dashboards;
CREATE TABLE user_dashboards (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT(11) NOT NULL,
  name VARCHAR(255) NOT NULL,
  INDEX idx(user_id, name(5))
);

DROP TABLE IF EXISTS project_dashboards;
CREATE TABLE project_dashboards (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_id INT(11) NOT NULL,
  name VARCHAR(255) NOT NULL,
  INDEX idx(project_id, name(5))
);

DROP TABLE IF EXISTS project_dashboards_disabled_widgets;
CREATE TABLE project_dashboards_disabled_widgets (
    name VARCHAR(255) PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS dashboards_lines;
CREATE TABLE dashboards_lines (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  dashboard_id INT(11) UNSIGNED NOT NULL,
  dashboard_type VARCHAR(255) NOT NULL,
  layout VARCHAR(255) NOT NULL,
  `rank` INT(11) NOT NULL,
  INDEX idx(dashboard_id, dashboard_type(3))
);

DROP TABLE IF EXISTS dashboards_lines_columns;
CREATE TABLE dashboards_lines_columns (
  id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  line_id INT(11) UNSIGNED NOT NULL,
  `rank` INT(11) NOT NULL,
  INDEX idx(line_id)
);

DROP TABLE IF EXISTS dashboards_lines_columns_widgets;
CREATE TABLE dashboards_lines_columns_widgets (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    column_id INT(11) UNSIGNED NOT NULL,
    `rank` INT(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    content_id INT DEFAULT '0' NOT NULL,
    INDEX col_idx(column_id)
);

DROP TABLE IF EXISTS project_label;
CREATE TABLE project_label (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_outline TINYINT(1) NOT NULL DEFAULT 1,
    color VARCHAR(255) NOT NULL DEFAULT 'chrome-silver',
    INDEX project_idx(project_id)
);

DROP TABLE IF EXISTS project_membership_delegation;
CREATE TABLE project_membership_delegation (
    ugroup_id INT(11) NOT NULL PRIMARY KEY
);

DROP TABLE IF EXISTS project_ugroup_synchronized_membership;
CREATE TABLE project_ugroup_synchronized_membership (
    project_id INT(11) NOT NULL PRIMARY KEY,
    is_activated TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

DROP TABLE IF EXISTS project_banner;
CREATE TABLE project_banner (
    project_id INT(11) NOT NULL PRIMARY KEY,
    message text
);

DROP TABLE IF EXISTS platform_banner;
CREATE TABLE platform_banner (
    message text,
    importance VARCHAR(8),
    expiration_date INT(11) UNSIGNED DEFAULT NULL
);

DROP TABLE IF EXISTS release_note_link;
CREATE TABLE release_note_link (
    enforce_one_row_table ENUM('SHOULD_HAVE_AT_MOST_ONE_ROW') NOT NULL PRIMARY KEY DEFAULT 'SHOULD_HAVE_AT_MOST_ONE_ROW',
    actual_link TEXT,
    tuleap_version TEXT NOT NULL
);

DROP TABLE IF EXISTS invitations;
CREATE TABLE invitations(
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    created_on INT(11) NOT NULL,
    from_user_id INT(11) NOT NULL,
    to_email TEXT NOT NULL,
    to_user_id INT(11) NULL,
    to_project_id INT NULL,
    custom_message TEXT NULL,
    status VARCHAR(10),
    created_user_id INT(11) NULL,
    verifier TEXT NOT NULL DEFAULT '',
    INDEX idx(created_on, from_user_id),
    INDEX idx_email(to_email(20)),
    INDEX idx_created(created_user_id, status, to_email(20))
) ENGINE=InnoDB;

DROP TABLE IF EXISTS project_background;
CREATE TABLE project_background(
    project_id INT(11) NOT NULL PRIMARY KEY,
    background VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE oauth2_server_oidc_signing_key (
    public_key TEXT NOT NULL,
    private_key BLOB NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    INDEX idx_expiration_date (expiration_date)
) ENGINE=InnoDB;

CREATE TABLE oauth2_server_app(
     id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
     project_id INT(11),
     name VARCHAR(255) NOT NULL,
     redirect_endpoint TEXT NOT NULL,
     verifier VARCHAR(255) NOT NULL,
     use_pkce BOOLEAN NOT NULL,
     app_type VARCHAR(255) NOT NULL,
     INDEX idx_project_id(project_id)
) ENGINE=InnoDB;

CREATE TABLE oauth2_authorization_code(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    app_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    has_already_been_used BOOLEAN NOT NULL,
    pkce_code_challenge BINARY(32),
    oidc_nonce TEXT,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_app_id (app_id),
    INDEX idx_user_app_id (user_id, app_id)
) ENGINE=InnoDB;

CREATE TABLE oauth2_refresh_token (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    authorization_code_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    has_already_been_used BOOLEAN NOT NULL,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_authorization_code (authorization_code_id)
) ENGINE=InnoDB;

CREATE TABLE oauth2_access_token (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    authorization_code_id INT(11) NOT NULL,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_authorization_code (authorization_code_id)
) ENGINE=InnoDB;

CREATE TABLE oauth2_access_token_scope (
    access_token_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (access_token_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE oauth2_refresh_token_scope (
    refresh_token_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (refresh_token_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE oauth2_authorization_code_scope (
    auth_code_id INT(11) NOT NULL,
    scope_key VARCHAR(255) NOT NULL,
    PRIMARY KEY (auth_code_id, scope_key)
) ENGINE=InnoDB;

CREATE TABLE webauthn_credential_source (
    public_key_credential_id VARCHAR(255) NOT NULL PRIMARY KEY,
    type VARCHAR(32) NOT NULL,
    transports TEXT NOT NULL,
    attestation_type VARCHAR(32) NOT NULL,
    trust_path TEXT NOT NULL,
    aaguid VARCHAR(64) NOT NULL,
    credential_public_key BLOB NOT NULL,
    user_id INT NOT NULL,
    counter INT UNSIGNED NOT NULL,
    other_ui TEXT,
    name VARCHAR(255) NOT NULL DEFAULT '',
    created_at INT UNSIGNED NOT NULL,
    last_use INT UNSIGNED NOT NULL,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

CREATE TABLE webauthn_challenge (
    user_id INT NOT NULL PRIMARY KEY,
    challenge BLOB NOT NULL,
    expiration_date INT UNSIGNED NOT NULL,
    INDEX idx_expiration_date (expiration_date)
) ENGINE=InnoDB;

CREATE TABLE filtered_outbound_http_requests (
    last_blocked INT UNSIGNED NOT NULL,
    seen_by_system_check BOOLEAN
) ENGINE=InnoDB;
#
# EOF
#
