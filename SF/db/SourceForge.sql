# MySQL dump 8.8
#
# Host: localhost    Database: alexandria
#--------------------------------------------------------
# Server version	3.23.22-beta

#
# Table structure for table 'activity_log'
#

CREATE TABLE activity_log (
  day int(11) DEFAULT '0' NOT NULL,
  hour int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  browser varchar(8) DEFAULT 'OTHER' NOT NULL,
  ver float(10,2) DEFAULT '0.00' NOT NULL,
  platform varchar(8) DEFAULT 'OTHER' NOT NULL,
  time int(11) DEFAULT '0' NOT NULL,
  page text,
  type int(11) DEFAULT '0' NOT NULL,
  KEY idx_activity_log_day (day),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (type)
);

#
# Table structure for table 'activity_log_old'
#

CREATE TABLE activity_log_old (
  day int(11) DEFAULT '0' NOT NULL,
  hour int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  browser varchar(8) DEFAULT 'OTHER' NOT NULL,
  ver float(10,2) DEFAULT '0.00' NOT NULL,
  platform varchar(8) DEFAULT 'OTHER' NOT NULL,
  time int(11) DEFAULT '0' NOT NULL,
  page text,
  type int(11) DEFAULT '0' NOT NULL,
  KEY idx_activity_log_day (day),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (type)
);

#
# Table structure for table 'activity_log_old_old'
#

CREATE TABLE activity_log_old_old (
  day int(11) DEFAULT '0' NOT NULL,
  hour int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  browser varchar(8) DEFAULT 'OTHER' NOT NULL,
  ver float(10,2) DEFAULT '0.00' NOT NULL,
  platform varchar(8) DEFAULT 'OTHER' NOT NULL,
  time int(11) DEFAULT '0' NOT NULL,
  page text,
  type int(11) DEFAULT '0' NOT NULL,
  KEY idx_activity_log_day (day),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (type)
);

#
# Table structure for table 'bug'
#

CREATE TABLE bug (
  bug_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  status_id int(11) DEFAULT '0' NOT NULL,
  priority int(11) DEFAULT '0' NOT NULL,
  category_id int(11) DEFAULT '0' NOT NULL,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  assigned_to int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  summary text,
  details text,
  close_date int(11),
  bug_group_id int(11) DEFAULT '0' NOT NULL,
  resolution_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (bug_id),
  KEY idx_bug_group_id (group_id)
);

#
# Table structure for table 'bug_bug_dependencies'
#

CREATE TABLE bug_bug_dependencies (
  bug_depend_id int(11) NOT NULL auto_increment,
  bug_id int(11) DEFAULT '0' NOT NULL,
  is_dependent_on_bug_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (bug_depend_id),
  KEY idx_bug_bug_dependencies_bug_id (bug_id),
  KEY idx_bug_bug_is_dependent_on_task_id (is_dependent_on_bug_id)
);

#
# Table structure for table 'bug_canned_responses'
#

CREATE TABLE bug_canned_responses (
  bug_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  title text,
  body text,
  PRIMARY KEY (bug_canned_id),
  KEY idx_bug_canned_response_group_id (group_id)
);

#
# Table structure for table 'bug_category'
#

CREATE TABLE bug_category (
  bug_category_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  category_name text,
  PRIMARY KEY (bug_category_id),
  KEY idx_bug_category_group_id (group_id)
);

#
# Table structure for table 'bug_filter'
#

CREATE TABLE bug_filter (
  filter_id int(11) NOT NULL auto_increment,
  user_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  sql_clause text DEFAULT '' NOT NULL,
  is_active int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (filter_id)
);

#
# Table structure for table 'bug_group'
#

CREATE TABLE bug_group (
  bug_group_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  group_name text DEFAULT '' NOT NULL,
  PRIMARY KEY (bug_group_id),
  KEY idx_bug_group_group_id (group_id)
);

#
# Table structure for table 'bug_history'
#

CREATE TABLE bug_history (
  bug_history_id int(11) NOT NULL auto_increment,
  bug_id int(11) DEFAULT '0' NOT NULL,
  field_name text DEFAULT '' NOT NULL,
  old_value text DEFAULT '' NOT NULL,
  mod_by int(11) DEFAULT '0' NOT NULL,
  date int(11),
  PRIMARY KEY (bug_history_id),
  KEY idx_bug_history_bug_id (bug_id)
);

#
# Table structure for table 'bug_resolution'
#

CREATE TABLE bug_resolution (
  resolution_id int(11) NOT NULL auto_increment,
  resolution_name text DEFAULT '' NOT NULL,
  PRIMARY KEY (resolution_id)
);

#
# Table structure for table 'bug_status'
#

CREATE TABLE bug_status (
  status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY (status_id)
);

#
# Table structure for table 'bug_task_dependencies'
#

CREATE TABLE bug_task_dependencies (
  bug_depend_id int(11) NOT NULL auto_increment,
  bug_id int(11) DEFAULT '0' NOT NULL,
  is_dependent_on_task_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (bug_depend_id),
  KEY idx_bug_task_dependencies_bug_id (bug_id),
  KEY idx_bug_task_is_dependent_on_task_id (is_dependent_on_task_id)
);

#
# Table structure for table 'db_images'
#

CREATE TABLE db_images (
  id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  description text DEFAULT '' NOT NULL,
  bin_data longblob DEFAULT '' NOT NULL,
  filename text DEFAULT '' NOT NULL,
  filesize int(11) DEFAULT '0' NOT NULL,
  filetype text DEFAULT '' NOT NULL,
  width int(11) DEFAULT '0' NOT NULL,
  height int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY idx_db_images_group (group_id)
);

#
# Table structure for table 'doc_data'
#

CREATE TABLE doc_data (
  docid int(11) NOT NULL auto_increment,
  stateid int(11) DEFAULT '0' NOT NULL,
  title varchar(255) DEFAULT '' NOT NULL,
  data text DEFAULT '' NOT NULL,
  updatedate int(11) DEFAULT '0' NOT NULL,
  createdate int(11) DEFAULT '0' NOT NULL,
  created_by int(11) DEFAULT '0' NOT NULL,
  doc_group int(11) DEFAULT '0' NOT NULL,
  description text,
  PRIMARY KEY (docid),
  KEY idx_doc_group_doc_group (doc_group)
);

#
# Table structure for table 'doc_groups'
#

CREATE TABLE doc_groups (
  doc_group int(12) NOT NULL auto_increment,
  groupname varchar(255) DEFAULT '' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (doc_group),
  KEY idx_doc_groups_group (group_id)
);

#
# Table structure for table 'doc_states'
#

CREATE TABLE doc_states (
  stateid int(11) NOT NULL auto_increment,
  name varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (stateid)
);

#
# Table structure for table 'filedownload_log'
#

CREATE TABLE filedownload_log (
  user_id int(11) DEFAULT '0' NOT NULL,
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  time int(11) DEFAULT '0' NOT NULL,
  KEY all_idx (user_id,filerelease_id),
  KEY time_idx (time),
  KEY filerelease_id_idx (filerelease_id)
);

#
# Table structure for table 'filemodule'
#

CREATE TABLE filemodule (
  filemodule_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  module_name varchar(40),
  recent_filerelease varchar(20) DEFAULT '' NOT NULL,
  PRIMARY KEY (filemodule_id),
  KEY idx_filemodule_group_id (group_id)
);

#
# Table structure for table 'filemodule_monitor'
#

CREATE TABLE filemodule_monitor (
  filemodule_id int(11) DEFAULT '0' NOT NULL,
  user_id int(11) DEFAULT '0' NOT NULL,
  KEY idx_filemodule_monitor_id (filemodule_id)
);

#
# Table structure for table 'filerelease'
#

CREATE TABLE filerelease (
  filerelease_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  user_id int(11) DEFAULT '0' NOT NULL,
  unix_box varchar(20) DEFAULT 'remission' NOT NULL,
  unix_partition int(11) DEFAULT '0' NOT NULL,
  text_notes text,
  text_changes text,
  release_version varchar(20),
  filename varchar(80),
  filemodule_id int(11) DEFAULT '0' NOT NULL,
  file_type varchar(50),
  release_time int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  file_size int(11),
  post_time int(11) DEFAULT '0' NOT NULL,
  text_format int(11) DEFAULT '0' NOT NULL,
  downloads_week int(11) DEFAULT '0' NOT NULL,
  status char(1) DEFAULT 'N' NOT NULL,
  old_filename varchar(80) DEFAULT '' NOT NULL,
  PRIMARY KEY (filerelease_id),
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
  group_forum_id int(11) DEFAULT '0' NOT NULL,
  posted_by int(11) DEFAULT '0' NOT NULL,
  subject text DEFAULT '' NOT NULL,
  body text DEFAULT '' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  is_followup_to int(11) DEFAULT '0' NOT NULL,
  thread_id int(11) DEFAULT '0' NOT NULL,
  has_followups int(11) DEFAULT '0',
  PRIMARY KEY (msg_id),
  KEY idx_forum_group_forum_id (group_forum_id),
  KEY idx_forum_is_followup_to (is_followup_to),
  KEY idx_forum_thread_id (thread_id),
  KEY idx_forum_id_date (group_forum_id,date),
  KEY idx_forum_id_date_followup (group_forum_id,date,is_followup_to),
  KEY idx_forum_thread_date_followup (thread_id,date,is_followup_to)
);

#
# Table structure for table 'forum_agg_msg_count'
#

CREATE TABLE forum_agg_msg_count (
  group_forum_id int(11) DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (group_forum_id)
);

#
# Table structure for table 'forum_group_list'
#

CREATE TABLE forum_group_list (
  group_forum_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  forum_name text DEFAULT '' NOT NULL,
  is_public int(11) DEFAULT '0' NOT NULL,
  description text,
  PRIMARY KEY (group_forum_id),
  KEY idx_forum_group_list_group_id (group_id)
);

#
# Table structure for table 'forum_monitored_forums'
#

CREATE TABLE forum_monitored_forums (
  monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) DEFAULT '0' NOT NULL,
  user_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (monitor_id),
  KEY idx_forum_monitor_thread_id (forum_id),
  KEY idx_forum_monitor_combo_id (forum_id,user_id)
);

#
# Table structure for table 'forum_saved_place'
#

CREATE TABLE forum_saved_place (
  saved_place_id int(11) NOT NULL auto_increment,
  user_id int(11) DEFAULT '0' NOT NULL,
  forum_id int(11) DEFAULT '0' NOT NULL,
  save_date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (saved_place_id)
);

#
# Table structure for table 'forum_thread_id'
#

CREATE TABLE forum_thread_id (
  thread_id int(11) NOT NULL auto_increment,
  PRIMARY KEY (thread_id)
);

#
# Table structure for table 'foundry_data'
#

CREATE TABLE foundry_data (
  foundry_id int(11) NOT NULL auto_increment,
  freeform1_html text,
  freeform2_html text,
  sponsor1_html text,
  sponsor2_html text,
  guide_image_id int(11) DEFAULT '0' NOT NULL,
  logo_image_id int(11) DEFAULT '0' NOT NULL,
  trove_categories text,
  PRIMARY KEY (foundry_id)
);

#
# Table structure for table 'foundry_news'
#

CREATE TABLE foundry_news (
  foundry_news_id int(11) NOT NULL auto_increment,
  foundry_id int(11) DEFAULT '0' NOT NULL,
  news_id int(11) DEFAULT '0' NOT NULL,
  approve_date int(11) DEFAULT '0' NOT NULL,
  is_approved int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (foundry_news_id),
  KEY idx_foundry_news_foundry (foundry_id),
  KEY idx_foundry_news_foundry_approved_date (foundry_id,is_approved,approve_date),
  KEY idx_foundry_news_foundry_approved (foundry_id,is_approved)
);

#
# Table structure for table 'foundry_preferred_projects'
#

CREATE TABLE foundry_preferred_projects (
  foundry_project_id int(11) NOT NULL auto_increment,
  foundry_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  rank int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (foundry_project_id),
  KEY idx_foundry_project_group (group_id),
  KEY idx_foundry_project_group_rank (group_id,rank)
);

#
# Table structure for table 'foundry_projects'
#

CREATE TABLE foundry_projects (
  id int(11) NOT NULL auto_increment,
  foundry_id int(11) DEFAULT '0' NOT NULL,
  project_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY idx_foundry_projects_foundry (foundry_id)
);

#
# Table structure for table 'frs_dlstats_agg'
#

CREATE TABLE frs_dlstats_agg (
  file_id int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  downloads_http int(11) DEFAULT '0' NOT NULL,
  downloads_ftp int(11) DEFAULT '0' NOT NULL,
  KEY file_id_idx (file_id),
  KEY day_idx (day),
  KEY downloads_http_idx (downloads_http),
  KEY downloads_ftp_idx (downloads_ftp)
);

#
# Table structure for table 'frs_dlstats_file_agg'
#

CREATE TABLE frs_dlstats_file_agg (
  file_id int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_dlstats_file_file_id (file_id),
  KEY idx_dlstats_file_day (day),
  KEY idx_dlstats_file_down (downloads)
);

#
# Table structure for table 'frs_dlstats_filetotal_agg'
#

CREATE TABLE frs_dlstats_filetotal_agg (
  file_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_stats_agr_tmp_fid (file_id)
);

#
# Table structure for table 'frs_dlstats_group_agg'
#

CREATE TABLE frs_dlstats_group_agg (
  group_id int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY group_id_idx (group_id),
  KEY day_idx (day),
  KEY downloads_idx (downloads)
);

#
# Table structure for table 'frs_dlstats_grouptotal_agg'
#

CREATE TABLE frs_dlstats_grouptotal_agg (
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_stats_agr_tmp_gid (group_id)
);

#
# Table structure for table 'frs_file'
#

CREATE TABLE frs_file (
  file_id int(11) NOT NULL auto_increment,
  filename text,
  release_id int(11) DEFAULT '0' NOT NULL,
  type_id int(11) DEFAULT '0' NOT NULL,
  processor_id int(11) DEFAULT '0' NOT NULL,
  release_time int(11) DEFAULT '0' NOT NULL,
  file_size int(11) DEFAULT '0' NOT NULL,
  post_date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (file_id),
  KEY idx_frs_file_release_id (release_id),
  KEY idx_frs_file_type (type_id),
  KEY idx_frs_file_date (post_date),
  KEY idx_frs_file_processor (processor_id),
  KEY idx_frs_file_name (filename(45))
);

#
# Table structure for table 'frs_filetype'
#

CREATE TABLE frs_filetype (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (type_id)
);

#
# Table structure for table 'frs_package'
#

CREATE TABLE frs_package (
  package_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  name text,
  status_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (package_id),
  KEY idx_package_group_id (group_id)
);

#
# Table structure for table 'frs_processor'
#

CREATE TABLE frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (processor_id)
);

#
# Table structure for table 'frs_release'
#

CREATE TABLE frs_release (
  release_id int(11) NOT NULL auto_increment,
  package_id int(11) DEFAULT '0' NOT NULL,
  name text,
  notes text,
  changes text,
  status_id int(11) DEFAULT '0' NOT NULL,
  preformatted int(11) DEFAULT '0' NOT NULL,
  release_date int(11) DEFAULT '0' NOT NULL,
  released_by int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (release_id),
  KEY idx_frs_release_by (released_by),
  KEY idx_frs_release_date (release_date),
  KEY idx_frs_release_package (package_id)
);

#
# Table structure for table 'frs_status'
#

CREATE TABLE frs_status (
  status_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (status_id)
);

#
# Table structure for table 'group_cvs_history'
#

CREATE TABLE group_cvs_history (
  group_id int(11) DEFAULT '0' NOT NULL,
  user_name varchar(80) DEFAULT '' NOT NULL,
  cvs_commits int(11) DEFAULT '0' NOT NULL,
  cvs_commits_wk int(11) DEFAULT '0' NOT NULL,
  cvs_adds int(11) DEFAULT '0' NOT NULL,
  cvs_adds_wk int(11) DEFAULT '0' NOT NULL,
  KEY group_id_idx (group_id),
  KEY user_name_idx (user_name)
);

#
# Table structure for table 'group_history'
#

CREATE TABLE group_history (
  group_history_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  field_name text DEFAULT '' NOT NULL,
  old_value text DEFAULT '' NOT NULL,
  mod_by int(11) DEFAULT '0' NOT NULL,
  date int(11),
  PRIMARY KEY (group_history_id),
  KEY idx_group_history_group_id (group_id)
);

#
# Table structure for table 'group_type'
#

CREATE TABLE group_type (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (type_id)
);

#
# Table structure for table 'groups'
#

CREATE TABLE groups (
  group_id int(11) NOT NULL auto_increment,
  group_name varchar(40),
  homepage varchar(128),
  is_public int(11) DEFAULT '0' NOT NULL,
  status char(1) DEFAULT 'A' NOT NULL,
  unix_group_name varchar(30) DEFAULT '' NOT NULL,
  unix_box varchar(20) DEFAULT 'shell1' NOT NULL,
  http_domain varchar(80),
  short_description varchar(255),
  cvs_box varchar(20) DEFAULT 'cvs1' NOT NULL,
  license varchar(16),
  register_purpose text,
  license_other text,
  register_time int(11) DEFAULT '0' NOT NULL,
  use_bugs int(11) DEFAULT '1' NOT NULL,
  rand_hash text,
  use_mail int(11) DEFAULT '1' NOT NULL,
  use_survey int(11) DEFAULT '1' NOT NULL,
  use_patch int(11) DEFAULT '1' NOT NULL,
  use_forum int(11) DEFAULT '1' NOT NULL,
  use_pm int(11) DEFAULT '1' NOT NULL,
  use_cvs int(11) DEFAULT '1' NOT NULL,
  use_news int(11) DEFAULT '1' NOT NULL,
  use_support int(11) DEFAULT '1' NOT NULL,
  new_bug_address text DEFAULT '' NOT NULL,
  new_patch_address text DEFAULT '' NOT NULL,
  new_support_address text DEFAULT '' NOT NULL,
  type int(11) DEFAULT '1' NOT NULL,
  use_docman int(11) DEFAULT '1' NOT NULL,
  send_all_bugs int(11) DEFAULT '0' NOT NULL,
  send_all_patches int(11) DEFAULT '0' NOT NULL,
  send_all_support int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (group_id),
  KEY idx_groups_status (status),
  KEY idx_groups_public (is_public),
  KEY idx_groups_unix (unix_group_name),
  KEY idx_groups_type (type)
);

#
# Table structure for table 'image'
#

CREATE TABLE image (
  image_id int(11) NOT NULL auto_increment,
  image_category int(11) DEFAULT '1' NOT NULL,
  image_type varchar(40) DEFAULT '' NOT NULL,
  image_data blob,
  group_id int(11) DEFAULT '0' NOT NULL,
  image_bytes int(11) DEFAULT '0' NOT NULL,
  image_caption text,
  organization_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (image_id),
  KEY image_category_idx (image_category),
  KEY image_type_idx (image_type),
  KEY group_id_idx (group_id)
);

#
# Table structure for table 'intel_agreement'
#

CREATE TABLE intel_agreement (
  user_id int(11) DEFAULT '0' NOT NULL,
  message text,
  is_approved int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (user_id)
);

#
# Table structure for table 'mail_group_list'
#

CREATE TABLE mail_group_list (
  group_list_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  list_name text,
  is_public int(11) DEFAULT '0' NOT NULL,
  password varchar(16),
  list_admin int(11) DEFAULT '0' NOT NULL,
  status int(11) DEFAULT '0' NOT NULL,
  description text,
  PRIMARY KEY (group_list_id),
  KEY idx_mail_group_list_group (group_id)
);

#
# Table structure for table 'mailaliases'
#

CREATE TABLE mailaliases (
  mailaliases_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  domain varchar(80),
  user_name varchar(20),
  email_forward varchar(255),
  PRIMARY KEY (mailaliases_id)
);

#
# Table structure for table 'news_bytes'
#

CREATE TABLE news_bytes (
  id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  is_approved int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  forum_id int(11) DEFAULT '0' NOT NULL,
  summary text,
  details text,
  PRIMARY KEY (id),
  KEY idx_news_bytes_forum (forum_id),
  KEY idx_news_bytes_group (group_id),
  KEY idx_news_bytes_approved (is_approved)
);

#
# Table structure for table 'patch'
#

CREATE TABLE patch (
  patch_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  patch_status_id int(11) DEFAULT '0' NOT NULL,
  patch_category_id int(11) DEFAULT '0' NOT NULL,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  assigned_to int(11) DEFAULT '0' NOT NULL,
  open_date int(11) DEFAULT '0' NOT NULL,
  summary text,
  code mediumtext,
  close_date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (patch_id),
  KEY idx_patch_group_id (group_id)
);

#
# Table structure for table 'patch_category'
#

CREATE TABLE patch_category (
  patch_category_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  category_name text DEFAULT '' NOT NULL,
  PRIMARY KEY (patch_category_id),
  KEY idx_patch_group_group_id (group_id)
);

#
# Table structure for table 'patch_history'
#

CREATE TABLE patch_history (
  patch_history_id int(11) NOT NULL auto_increment,
  patch_id int(11) DEFAULT '0' NOT NULL,
  field_name text DEFAULT '' NOT NULL,
  old_value text DEFAULT '' NOT NULL,
  mod_by int(11) DEFAULT '0' NOT NULL,
  date int(11),
  PRIMARY KEY (patch_history_id),
  KEY idx_patch_history_patch_id (patch_id)
);

#
# Table structure for table 'patch_status'
#

CREATE TABLE patch_status (
  patch_status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY (patch_status_id)
);

#
# Table structure for table 'people_job'
#

CREATE TABLE people_job (
  job_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  created_by int(11) DEFAULT '0' NOT NULL,
  title text,
  description text,
  date int(11) DEFAULT '0' NOT NULL,
  status_id int(11) DEFAULT '0' NOT NULL,
  category_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (job_id)
);

#
# Table structure for table 'people_job_category'
#

CREATE TABLE people_job_category (
  category_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (category_id)
);

#
# Table structure for table 'people_job_inventory'
#

CREATE TABLE people_job_inventory (
  job_inventory_id int(11) NOT NULL auto_increment,
  job_id int(11) DEFAULT '0' NOT NULL,
  skill_id int(11) DEFAULT '0' NOT NULL,
  skill_level_id int(11) DEFAULT '0' NOT NULL,
  skill_year_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (job_inventory_id)
);

#
# Table structure for table 'people_job_status'
#

CREATE TABLE people_job_status (
  status_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (status_id)
);

#
# Table structure for table 'people_skill'
#

CREATE TABLE people_skill (
  skill_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (skill_id)
);

#
# Table structure for table 'people_skill_inventory'
#

CREATE TABLE people_skill_inventory (
  skill_inventory_id int(11) NOT NULL auto_increment,
  user_id int(11) DEFAULT '0' NOT NULL,
  skill_id int(11) DEFAULT '0' NOT NULL,
  skill_level_id int(11) DEFAULT '0' NOT NULL,
  skill_year_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (skill_inventory_id)
);

#
# Table structure for table 'people_skill_level'
#

CREATE TABLE people_skill_level (
  skill_level_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (skill_level_id)
);

#
# Table structure for table 'people_skill_year'
#

CREATE TABLE people_skill_year (
  skill_year_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY (skill_year_id)
);

#
# Table structure for table 'project_assigned_to'
#

CREATE TABLE project_assigned_to (
  project_assigned_id int(11) NOT NULL auto_increment,
  project_task_id int(11) DEFAULT '0' NOT NULL,
  assigned_to_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (project_assigned_id),
  KEY idx_project_assigned_to_task_id (project_task_id),
  KEY idx_project_assigned_to_assigned_to (assigned_to_id)
);

#
# Table structure for table 'project_counts_tmp'
#

CREATE TABLE project_counts_tmp (
  group_id int(11),
  type text,
  count float(8,5)
);

#
# Table structure for table 'project_counts_weekly_tmp'
#

CREATE TABLE project_counts_weekly_tmp (
  group_id int(11),
  type text,
  count float(8,5)
);

#
# Table structure for table 'project_dependencies'
#

CREATE TABLE project_dependencies (
  project_depend_id int(11) NOT NULL auto_increment,
  project_task_id int(11) DEFAULT '0' NOT NULL,
  is_dependent_on_task_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (project_depend_id),
  KEY idx_project_dependencies_task_id (project_task_id),
  KEY idx_project_is_dependent_on_task_id (is_dependent_on_task_id)
);

#
# Table structure for table 'project_group_list'
#

CREATE TABLE project_group_list (
  group_project_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  project_name text DEFAULT '' NOT NULL,
  is_public int(11) DEFAULT '0' NOT NULL,
  description text,
  PRIMARY KEY (group_project_id),
  KEY idx_project_group_list_group_id (group_id)
);

#
# Table structure for table 'project_history'
#

CREATE TABLE project_history (
  project_history_id int(11) NOT NULL auto_increment,
  project_task_id int(11) DEFAULT '0' NOT NULL,
  field_name text DEFAULT '' NOT NULL,
  old_value text DEFAULT '' NOT NULL,
  mod_by int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (project_history_id),
  KEY idx_project_history_task_id (project_task_id)
);

#
# Table structure for table 'project_metric'
#

CREATE TABLE project_metric (
  ranking int(11) NOT NULL auto_increment,
  percentile float(8,2),
  group_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (ranking),
  KEY idx_project_metric_group (group_id)
);

#
# Table structure for table 'project_metric_tmp1'
#

CREATE TABLE project_metric_tmp1 (
  ranking int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  value float(8,5),
  PRIMARY KEY (ranking)
);

#
# Table structure for table 'project_metric_weekly_tmp1'
#

CREATE TABLE project_metric_weekly_tmp1 (
  ranking int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  value float(8,5),
  PRIMARY KEY (ranking)
);

#
# Table structure for table 'project_status'
#

CREATE TABLE project_status (
  status_id int(11) NOT NULL auto_increment,
  status_name text DEFAULT '' NOT NULL,
  PRIMARY KEY (status_id)
);

#
# Table structure for table 'project_task'
#

CREATE TABLE project_task (
  project_task_id int(11) NOT NULL auto_increment,
  group_project_id int(11) DEFAULT '0' NOT NULL,
  summary text DEFAULT '' NOT NULL,
  details text DEFAULT '' NOT NULL,
  percent_complete int(11) DEFAULT '0' NOT NULL,
  priority int(11) DEFAULT '0' NOT NULL,
  hours float(10,2) DEFAULT '0.00' NOT NULL,
  start_date int(11) DEFAULT '0' NOT NULL,
  end_date int(11) DEFAULT '0' NOT NULL,
  created_by int(11) DEFAULT '0' NOT NULL,
  status_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (project_task_id),
  KEY idx_project_task_group_project_id (group_project_id)
);

#
# Table structure for table 'project_weekly_metric'
#

CREATE TABLE project_weekly_metric (
  ranking int(11) NOT NULL auto_increment,
  percentile float(8,2),
  group_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (ranking),
  KEY idx_project_metric_weekly_group (group_id)
);

#
# Table structure for table 'session'
#

CREATE TABLE session (
  user_id int(11) DEFAULT '0' NOT NULL,
  session_hash char(32) DEFAULT '' NOT NULL,
  ip_addr char(15) DEFAULT '' NOT NULL,
  time int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (session_hash),
  KEY idx_session_user_id (user_id),
  KEY time_idx (time),
  KEY idx_session_time (time)
);

#
# Table structure for table 'snippet'
#

CREATE TABLE snippet (
  snippet_id int(11) NOT NULL auto_increment,
  created_by int(11) DEFAULT '0' NOT NULL,
  name text,
  description text,
  type int(11) DEFAULT '0' NOT NULL,
  language int(11) DEFAULT '0' NOT NULL,
  license text DEFAULT '' NOT NULL,
  category int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (snippet_id),
  KEY idx_snippet_language (language),
  KEY idx_snippet_category (category)
);

#
# Table structure for table 'snippet_package'
#

CREATE TABLE snippet_package (
  snippet_package_id int(11) NOT NULL auto_increment,
  created_by int(11) DEFAULT '0' NOT NULL,
  name text,
  description text,
  category int(11) DEFAULT '0' NOT NULL,
  language int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (snippet_package_id),
  KEY idx_snippet_package_language (language),
  KEY idx_snippet_package_category (category)
);

#
# Table structure for table 'snippet_package_item'
#

CREATE TABLE snippet_package_item (
  snippet_package_item_id int(11) NOT NULL auto_increment,
  snippet_package_version_id int(11) DEFAULT '0' NOT NULL,
  snippet_version_id int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (snippet_package_item_id),
  KEY idx_snippet_package_item_pkg_ver (snippet_package_version_id)
);

#
# Table structure for table 'snippet_package_version'
#

CREATE TABLE snippet_package_version (
  snippet_package_version_id int(11) NOT NULL auto_increment,
  snippet_package_id int(11) DEFAULT '0' NOT NULL,
  changes text,
  version text,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (snippet_package_version_id),
  KEY idx_snippet_package_version_pkg_id (snippet_package_id)
);

#
# Table structure for table 'snippet_version'
#

CREATE TABLE snippet_version (
  snippet_version_id int(11) NOT NULL auto_increment,
  snippet_id int(11) DEFAULT '0' NOT NULL,
  changes text,
  version text,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  code text,
  PRIMARY KEY (snippet_version_id),
  KEY idx_snippet_version_snippet_id (snippet_id)
);

#
# Table structure for table 'stats_agg_logo_by_day'
#

CREATE TABLE stats_agg_logo_by_day (
  day int(11),
  count int(11)
);

#
# Table structure for table 'stats_agg_logo_by_group'
#

CREATE TABLE stats_agg_logo_by_group (
  day int(11),
  group_id int(11),
  count int(11)
);

#
# Table structure for table 'stats_agg_pages_by_browser'
#

CREATE TABLE stats_agg_pages_by_browser (
  browser varchar(8),
  count int(11)
);

#
# Table structure for table 'stats_agg_pages_by_day'
#

CREATE TABLE stats_agg_pages_by_day (
  day int(11) DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL,
  KEY idx_pages_by_day_day (day)
);

#
# Table structure for table 'stats_agg_pages_by_day_old'
#

CREATE TABLE stats_agg_pages_by_day_old (
  day int(11),
  count int(11)
);

#
# Table structure for table 'stats_agg_site_by_day'
#

CREATE TABLE stats_agg_site_by_day (
  day int(11) DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'stats_agg_site_by_group'
#

CREATE TABLE stats_agg_site_by_group (
  day int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'stats_agr_filerelease'
#

CREATE TABLE stats_agr_filerelease (
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_stats_agr_tmp_fid (filerelease_id),
  KEY idx_stats_agr_tmp_gid (group_id)
);

#
# Table structure for table 'stats_agr_project'
#

CREATE TABLE stats_agr_project (
  group_id int(11) DEFAULT '0' NOT NULL,
  group_ranking int(11) DEFAULT '0' NOT NULL,
  group_metric float(8,5) DEFAULT '0.00000' NOT NULL,
  developers smallint(6) DEFAULT '0' NOT NULL,
  file_releases smallint(6) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  logo_views int(11) DEFAULT '0' NOT NULL,
  msg_posted smallint(6) DEFAULT '0' NOT NULL,
  msg_uniq_auth smallint(6) DEFAULT '0' NOT NULL,
  bugs_opened smallint(6) DEFAULT '0' NOT NULL,
  bugs_closed smallint(6) DEFAULT '0' NOT NULL,
  support_opened smallint(6) DEFAULT '0' NOT NULL,
  support_closed smallint(6) DEFAULT '0' NOT NULL,
  patches_opened smallint(6) DEFAULT '0' NOT NULL,
  patches_closed smallint(6) DEFAULT '0' NOT NULL,
  tasks_opened smallint(6) DEFAULT '0' NOT NULL,
  tasks_closed smallint(6) DEFAULT '0' NOT NULL,
  help_requests smallint(6) DEFAULT '0' NOT NULL,
  cvs_checkouts smallint(6) DEFAULT '0' NOT NULL,
  cvs_commits smallint(6) DEFAULT '0' NOT NULL,
  cvs_adds smallint(6) DEFAULT '0' NOT NULL,
  KEY idx_project_agr_log_group (group_id)
);

#
# Table structure for table 'stats_ftp_downloads'
#

CREATE TABLE stats_ftp_downloads (
  day int(11) DEFAULT '0' NOT NULL,
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_ftpdl_day (day),
  KEY idx_ftpdl_fid (filerelease_id),
  KEY idx_ftpdl_group_id (group_id)
);

#
# Table structure for table 'stats_http_downloads'
#

CREATE TABLE stats_http_downloads (
  day int(11) DEFAULT '0' NOT NULL,
  filerelease_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  KEY idx_httpdl_day (day),
  KEY idx_httpdl_fid (filerelease_id),
  KEY idx_httpdl_group_id (group_id)
);

#
# Table structure for table 'stats_project'
#

CREATE TABLE stats_project (
  month int(11) DEFAULT '0' NOT NULL,
  week int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  group_ranking int(11) DEFAULT '0' NOT NULL,
  group_metric float(8,5) DEFAULT '0.00000' NOT NULL,
  developers smallint(6) DEFAULT '0' NOT NULL,
  file_releases smallint(6) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  subdomain_views int(11) DEFAULT '0' NOT NULL,
  msg_posted smallint(6) DEFAULT '0' NOT NULL,
  msg_uniq_auth smallint(6) DEFAULT '0' NOT NULL,
  bugs_opened smallint(6) DEFAULT '0' NOT NULL,
  bugs_closed smallint(6) DEFAULT '0' NOT NULL,
  support_opened smallint(6) DEFAULT '0' NOT NULL,
  support_closed smallint(6) DEFAULT '0' NOT NULL,
  patches_opened smallint(6) DEFAULT '0' NOT NULL,
  patches_closed smallint(6) DEFAULT '0' NOT NULL,
  tasks_opened smallint(6) DEFAULT '0' NOT NULL,
  tasks_closed smallint(6) DEFAULT '0' NOT NULL,
  help_requests smallint(6) DEFAULT '0' NOT NULL,
  cvs_checkouts smallint(6) DEFAULT '0' NOT NULL,
  cvs_commits smallint(6) DEFAULT '0' NOT NULL,
  cvs_adds smallint(6) DEFAULT '0' NOT NULL,
  KEY idx_project_log_group (group_id),
  KEY idx_archive_project_month (month),
  KEY idx_archive_project_week (week),
  KEY idx_archive_project_day (day),
  KEY idx_archive_project_monthday (month,day)
);

#
# Table structure for table 'stats_project_tmp'
#

CREATE TABLE stats_project_tmp (
  month int(11) DEFAULT '0' NOT NULL,
  week int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  group_ranking int(11) DEFAULT '0' NOT NULL,
  group_metric float(8,5) DEFAULT '0.00000' NOT NULL,
  developers smallint(6) DEFAULT '0' NOT NULL,
  file_releases smallint(6) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  subdomain_views int(11) DEFAULT '0' NOT NULL,
  msg_posted smallint(6) DEFAULT '0' NOT NULL,
  msg_uniq_auth smallint(6) DEFAULT '0' NOT NULL,
  bugs_opened smallint(6) DEFAULT '0' NOT NULL,
  bugs_closed smallint(6) DEFAULT '0' NOT NULL,
  support_opened smallint(6) DEFAULT '0' NOT NULL,
  support_closed smallint(6) DEFAULT '0' NOT NULL,
  patches_opened smallint(6) DEFAULT '0' NOT NULL,
  patches_closed smallint(6) DEFAULT '0' NOT NULL,
  tasks_opened smallint(6) DEFAULT '0' NOT NULL,
  tasks_closed smallint(6) DEFAULT '0' NOT NULL,
  help_requests smallint(6) DEFAULT '0' NOT NULL,
  cvs_checkouts smallint(6) DEFAULT '0' NOT NULL,
  cvs_commits smallint(6) DEFAULT '0' NOT NULL,
  cvs_adds smallint(6) DEFAULT '0' NOT NULL,
  KEY idx_project_log_group (group_id),
  KEY idx_project_stats_day (day),
  KEY idx_project_stats_week (week),
  KEY idx_project_stats_month (month)
);

#
# Table structure for table 'stats_site'
#

CREATE TABLE stats_site (
  month int(11) DEFAULT '0' NOT NULL,
  week int(11) DEFAULT '0' NOT NULL,
  day int(11) DEFAULT '0' NOT NULL,
  site_views int(11) DEFAULT '0' NOT NULL,
  subdomain_views int(11) DEFAULT '0' NOT NULL,
  downloads int(11) DEFAULT '0' NOT NULL,
  uniq_users int(11) DEFAULT '0' NOT NULL,
  sessions int(11) DEFAULT '0' NOT NULL,
  total_users int(11) DEFAULT '0' NOT NULL,
  new_users int(11) DEFAULT '0' NOT NULL,
  new_projects int(11) DEFAULT '0' NOT NULL,
  KEY idx_stats_site_month (month),
  KEY idx_stats_site_week (week),
  KEY idx_stats_site_day (day),
  KEY idx_stats_site_monthday (month,day)
);

#
# Table structure for table 'support'
#

CREATE TABLE support (
  support_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  support_status_id int(11) DEFAULT '0' NOT NULL,
  support_category_id int(11) DEFAULT '0' NOT NULL,
  priority int(11) DEFAULT '0' NOT NULL,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  assigned_to int(11) DEFAULT '0' NOT NULL,
  open_date int(11) DEFAULT '0' NOT NULL,
  summary text,
  close_date int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (support_id),
  KEY idx_support_group_id (group_id)
);

#
# Table structure for table 'support_canned_responses'
#

CREATE TABLE support_canned_responses (
  support_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  title text,
  body text,
  PRIMARY KEY (support_canned_id),
  KEY idx_support_canned_response_group_id (group_id)
);

#
# Table structure for table 'support_category'
#

CREATE TABLE support_category (
  support_category_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  category_name text DEFAULT '' NOT NULL,
  PRIMARY KEY (support_category_id),
  KEY idx_support_group_group_id (group_id)
);

#
# Table structure for table 'support_history'
#

CREATE TABLE support_history (
  support_history_id int(11) NOT NULL auto_increment,
  support_id int(11) DEFAULT '0' NOT NULL,
  field_name text DEFAULT '' NOT NULL,
  old_value text DEFAULT '' NOT NULL,
  mod_by int(11) DEFAULT '0' NOT NULL,
  date int(11),
  PRIMARY KEY (support_history_id),
  KEY idx_support_history_support_id (support_id)
);

#
# Table structure for table 'support_messages'
#

CREATE TABLE support_messages (
  support_message_id int(11) NOT NULL auto_increment,
  support_id int(11) DEFAULT '0' NOT NULL,
  from_email text,
  date int(11) DEFAULT '0' NOT NULL,
  body text,
  PRIMARY KEY (support_message_id),
  KEY idx_support_messages_support_id (support_id)
);

#
# Table structure for table 'support_status'
#

CREATE TABLE support_status (
  support_status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY (support_status_id)
);

#
# Table structure for table 'survey_question_types'
#

CREATE TABLE survey_question_types (
  id int(11) NOT NULL auto_increment,
  type text DEFAULT '' NOT NULL,
  PRIMARY KEY (id)
);

#
# Table structure for table 'survey_questions'
#

CREATE TABLE survey_questions (
  question_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  question text DEFAULT '' NOT NULL,
  question_type int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (question_id),
  KEY idx_survey_questions_group (group_id)
);

#
# Table structure for table 'survey_rating_aggregate'
#

CREATE TABLE survey_rating_aggregate (
  type int(11) DEFAULT '0' NOT NULL,
  id int(11) DEFAULT '0' NOT NULL,
  response float DEFAULT '0' NOT NULL,
  count int(11) DEFAULT '0' NOT NULL,
  KEY idx_survey_rating_aggregate_type_id (type,id)
);

#
# Table structure for table 'survey_rating_response'
#

CREATE TABLE survey_rating_response (
  user_id int(11) DEFAULT '0' NOT NULL,
  type int(11) DEFAULT '0' NOT NULL,
  id int(11) DEFAULT '0' NOT NULL,
  response int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  KEY idx_survey_rating_responses_user_type_id (user_id,type,id),
  KEY idx_survey_rating_responses_type_id (type,id)
);

#
# Table structure for table 'survey_responses'
#

CREATE TABLE survey_responses (
  user_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  survey_id int(11) DEFAULT '0' NOT NULL,
  question_id int(11) DEFAULT '0' NOT NULL,
  response text DEFAULT '' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  KEY idx_survey_responses_user_survey (user_id,survey_id),
  KEY idx_survey_responses_user_survey_question (user_id,survey_id,question_id),
  KEY idx_survey_responses_survey_question (survey_id,question_id),
  KEY idx_survey_responses_group_id (group_id)
);

#
# Table structure for table 'surveys'
#

CREATE TABLE surveys (
  survey_id int(11) NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  survey_title text DEFAULT '' NOT NULL,
  survey_questions text DEFAULT '' NOT NULL,
  is_active int(11) DEFAULT '1' NOT NULL,
  PRIMARY KEY (survey_id),
  KEY idx_surveys_group (group_id)
);

#
# Table structure for table 'theme_prefs'
#

CREATE TABLE theme_prefs (
  user_id int(11) DEFAULT '0' NOT NULL,
  user_theme int(8) DEFAULT '0' NOT NULL,
  BODY_font char(80) DEFAULT '',
  BODY_size char(5) DEFAULT '',
  TITLEBAR_font char(80) DEFAULT '',
  TITLEBAR_size char(5) DEFAULT '',
  COLOR_TITLEBAR_BACK char(7) DEFAULT '',
  COLOR_LTBACK1 char(7) DEFAULT '',
  PRIMARY KEY (user_id)
);

#
# Table structure for table 'themes'
#

CREATE TABLE themes (
  theme_id int(11) NOT NULL auto_increment,
  dirname varchar(80),
  fullname varchar(80),
  PRIMARY KEY (theme_id)
);

#
# Table structure for table 'tmp_projs_releases_tmp'
#

CREATE TABLE tmp_projs_releases_tmp (
  year int(11) DEFAULT '0' NOT NULL,
  month int(11) DEFAULT '0' NOT NULL,
  total_proj int(11) DEFAULT '0' NOT NULL,
  total_releases int(11) DEFAULT '0' NOT NULL
);

#
# Table structure for table 'top_group'
#

CREATE TABLE top_group (
  group_id int(11) DEFAULT '0' NOT NULL,
  group_name varchar(40),
  downloads_all int(11) DEFAULT '0' NOT NULL,
  rank_downloads_all int(11) DEFAULT '0' NOT NULL,
  rank_downloads_all_old int(11) DEFAULT '0' NOT NULL,
  downloads_week int(11) DEFAULT '0' NOT NULL,
  rank_downloads_week int(11) DEFAULT '0' NOT NULL,
  rank_downloads_week_old int(11) DEFAULT '0' NOT NULL,
  userrank int(11) DEFAULT '0' NOT NULL,
  rank_userrank int(11) DEFAULT '0' NOT NULL,
  rank_userrank_old int(11) DEFAULT '0' NOT NULL,
  forumposts_week int(11) DEFAULT '0' NOT NULL,
  rank_forumposts_week int(11) DEFAULT '0' NOT NULL,
  rank_forumposts_week_old int(11) DEFAULT '0' NOT NULL,
  pageviews_proj int(11) DEFAULT '0' NOT NULL,
  rank_pageviews_proj int(11) DEFAULT '0' NOT NULL,
  rank_pageviews_proj_old int(11) DEFAULT '0' NOT NULL,
  KEY rank_downloads_all_idx (rank_downloads_all),
  KEY rank_downloads_week_idx (rank_downloads_week),
  KEY rank_userrank_idx (rank_userrank),
  KEY rank_forumposts_week_idx (rank_forumposts_week),
  KEY pageviews_proj_idx (pageviews_proj)
);

#
# Table structure for table 'trove_cat'
#

CREATE TABLE trove_cat (
  trove_cat_id int(11) NOT NULL auto_increment,
  version int(11) DEFAULT '0' NOT NULL,
  parent int(11) DEFAULT '0' NOT NULL,
  root_parent int(11) DEFAULT '0' NOT NULL,
  shortname varchar(80),
  fullname varchar(80),
  description varchar(255),
  count_subcat int(11) DEFAULT '0' NOT NULL,
  count_subproj int(11) DEFAULT '0' NOT NULL,
  fullpath text DEFAULT '' NOT NULL,
  fullpath_ids text,
  PRIMARY KEY (trove_cat_id),
  KEY parent_idx (parent),
  KEY root_parent_idx (root_parent),
  KEY version_idx (version)
);

#
# Table structure for table 'trove_group_link'
#

CREATE TABLE trove_group_link (
  trove_group_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) DEFAULT '0' NOT NULL,
  trove_cat_version int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  trove_cat_root int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (trove_group_id),
  KEY idx_trove_group_link_group_id (group_id),
  KEY idx_trove_group_link_cat_id (trove_cat_id)
);

#
# Table structure for table 'trove_treesums'
#

CREATE TABLE trove_treesums (
  trove_treesums_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) DEFAULT '0' NOT NULL,
  limit_1 int(11) DEFAULT '0' NOT NULL,
  subprojects int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (trove_treesums_id)
);

#
# Table structure for table 'user'
#

CREATE TABLE user (
  user_id int(11) NOT NULL auto_increment,
  user_name text DEFAULT '' NOT NULL,
  email text DEFAULT '' NOT NULL,
  user_pw varchar(32) DEFAULT '' NOT NULL,
  realname varchar(32) DEFAULT '' NOT NULL,
  status char(1) DEFAULT 'A' NOT NULL,
  shell varchar(20) DEFAULT '/bin/bash' NOT NULL,
  unix_pw varchar(40) DEFAULT '' NOT NULL,
  unix_status char(1) DEFAULT 'N' NOT NULL,
  unix_uid int(11) DEFAULT '0' NOT NULL,
  unix_box varchar(10) DEFAULT 'shell1' NOT NULL,
  add_date int(11) DEFAULT '0' NOT NULL,
  confirm_hash varchar(32),
  mail_siteupdates int(11) DEFAULT '0' NOT NULL,
  mail_va int(11) DEFAULT '0' NOT NULL,
  authorized_keys text,
  email_new text,
  people_view_skills int(11) DEFAULT '0' NOT NULL,
  people_resume text DEFAULT '' NOT NULL,
  timezone varchar(64) DEFAULT 'GMT',
  PRIMARY KEY (user_id),
  KEY idx_user_user (status)
);

#
# Table structure for table 'user_bookmarks'
#

CREATE TABLE user_bookmarks (
  bookmark_id int(11) NOT NULL auto_increment,
  user_id int(11) DEFAULT '0' NOT NULL,
  bookmark_url text,
  bookmark_title text,
  PRIMARY KEY (bookmark_id),
  KEY idx_user_bookmark_user_id (user_id)
);

#
# Table structure for table 'user_group'
#

CREATE TABLE user_group (
  user_group_id int(11) NOT NULL auto_increment,
  user_id int(11) DEFAULT '0' NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  admin_flags char(16) DEFAULT '' NOT NULL,
  bug_flags int(11) DEFAULT '0' NOT NULL,
  forum_flags int(11) DEFAULT '0' NOT NULL,
  project_flags int(11) DEFAULT '2' NOT NULL,
  patch_flags int(11) DEFAULT '1' NOT NULL,
  support_flags int(11) DEFAULT '1' NOT NULL,
  doc_flags int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (user_group_id),
  KEY idx_user_group_user_id (user_id),
  KEY idx_user_group_group_id (group_id),
  KEY bug_flags_idx (bug_flags),
  KEY forum_flags_idx (forum_flags),
  KEY project_flags_idx (project_flags),
  KEY admin_flags_idx (admin_flags)
);

#
# Table structure for table 'user_preferences'
#

CREATE TABLE user_preferences (
  user_id int(11) DEFAULT '0' NOT NULL,
  preference_name varchar(20),
  preference_value varchar(20),
  KEY idx_user_pref_user_id (user_id)
);

