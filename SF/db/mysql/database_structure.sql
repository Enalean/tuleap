#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    Create all the CodeX tables. (The Database must be created first by hand)
#
# MySQL dump 8.22
#
# Host: localhost    Database: sourceforge
#-------------------------------------------------------
# Server version	3.23.51-log

#
# Table structure for table 'activity_log'
#

CREATE TABLE activity_log (
  day int(11) NOT NULL default '0',
  hour int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  browser varchar(8) NOT NULL default 'OTHER',
  ver float(10,2) NOT NULL default '0.00',
  platform varchar(8) NOT NULL default 'OTHER',
  time int(11) NOT NULL default '0',
  page text,
  type int(11) NOT NULL default '0',
  KEY idx_activity_log_day (day),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (type)
) TYPE=MyISAM;

#
# Table structure for table 'activity_log_old'
#

CREATE TABLE activity_log_old (
  day int(11) NOT NULL default '0',
  hour int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  browser varchar(8) NOT NULL default 'OTHER',
  ver float(10,2) NOT NULL default '0.00',
  platform varchar(8) NOT NULL default 'OTHER',
  time int(11) NOT NULL default '0',
  page text,
  type int(11) NOT NULL default '0',
  KEY idx_activity_log_day (day),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (type)
) TYPE=MyISAM;

#
# Table structure for table 'activity_log_old_old'
#

CREATE TABLE activity_log_old_old (
  day int(11) NOT NULL default '0',
  hour int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  browser varchar(8) NOT NULL default 'OTHER',
  ver float(10,2) NOT NULL default '0.00',
  platform varchar(8) NOT NULL default 'OTHER',
  time int(11) NOT NULL default '0',
  page text,
  type int(11) NOT NULL default '0',
  KEY idx_activity_log_day (day),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (type)
) TYPE=MyISAM;

#
# Table structure for table 'bug'
#

CREATE TABLE bug (
  bug_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '1',
  severity int(11) NOT NULL default '5',
  category_id int(11) NOT NULL default '100',
  submitted_by int(11) NOT NULL default '100',
  assigned_to int(11) NOT NULL default '100',
  date int(11) NOT NULL default '0',
  summary text,
  details text,
  close_date int(11) default NULL,
  bug_group_id int(11) NOT NULL default '100',
  resolution_id int(11) NOT NULL default '100',
  category_version_id int(11) NOT NULL default '100',
  platform_version_id int(11) NOT NULL default '100',
  reproducibility_id int(11) NOT NULL default '100',
  size_id int(11) NOT NULL default '100',
  fix_release_id int(11) NOT NULL default '100',
  plan_release_id int(11) NOT NULL default '100',
  hours float(10,2) NOT NULL default '0.00',
  component_version varchar(255) NOT NULL default '',
  fix_release varchar(255) NOT NULL default '',
  plan_release varchar(255) NOT NULL default '',
  priority int(11) NOT NULL default '100',
  keywords varchar(255) NOT NULL default '',
  release_id int(11) NOT NULL default '100',
  release varchar(255) NOT NULL default '',
  originator_name varchar(255) NOT NULL default '',
  originator_email varchar(255) NOT NULL default '',
  originator_phone varchar(255) NOT NULL default '',
  custom_tf1 varchar(255) NOT NULL default '',
  custom_tf2 varchar(255) NOT NULL default '',
  custom_tf3 varchar(255) NOT NULL default '',
  custom_tf4 varchar(255) NOT NULL default '',
  custom_tf5 varchar(255) NOT NULL default '',
  custom_tf6 varchar(255) NOT NULL default '',
  custom_tf7 varchar(255) NOT NULL default '',
  custom_tf8 varchar(255) NOT NULL default '',
  custom_tf9 varchar(255) NOT NULL default '',
  custom_tf10 varchar(255) NOT NULL default '',
  custom_ta1 text NOT NULL,
  custom_ta2 text NOT NULL,
  custom_ta3 text NOT NULL,
  custom_ta4 text NOT NULL,
  custom_ta5 text NOT NULL,
  custom_ta6 text NOT NULL,
  custom_ta7 text NOT NULL,
  custom_ta8 text NOT NULL,
  custom_ta9 text NOT NULL,
  custom_ta10 text NOT NULL,
  custom_sb1 int(11) NOT NULL default '100',
  custom_sb2 int(11) NOT NULL default '100',
  custom_sb3 int(11) NOT NULL default '100',
  custom_sb4 int(11) NOT NULL default '100',
  custom_sb5 int(11) NOT NULL default '100',
  custom_sb6 int(11) NOT NULL default '100',
  custom_sb7 int(11) NOT NULL default '100',
  custom_sb8 int(11) NOT NULL default '100',
  custom_sb9 int(11) NOT NULL default '100',
  custom_sb10 int(11) NOT NULL default '100',
  custom_df1 int(11) NOT NULL default '0',
  custom_df2 int(11) NOT NULL default '0',
  custom_df3 int(11) NOT NULL default '0',
  custom_df4 int(11) NOT NULL default '0',
  custom_df5 int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_id),
  KEY idx_bug_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_bug_dependencies'
#

CREATE TABLE bug_bug_dependencies (
  bug_depend_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  is_dependent_on_bug_id int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_depend_id),
  KEY idx_bug_bug_dependencies_bug_id (bug_id),
  KEY idx_bug_bug_is_dependent_on_task_id (is_dependent_on_bug_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_canned_responses'
#

CREATE TABLE bug_canned_responses (
  bug_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  title text,
  body text,
  PRIMARY KEY  (bug_canned_id),
  KEY idx_bug_canned_response_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_cc' for carbon-copied people
# on bug email notification
#

CREATE TABLE bug_cc (
  bug_cc_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  comment text NOT NULL,
  date int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_cc_id),
  KEY bug_id_idx (bug_id)
) TYPE=MyISAM;


#
# Table structure for table 'bug_field'
#
# field_name  : the name of the field (must be indentical to the
#               column name in the bug table
# display_type: TF= text field, TA=text area, SB=Select Box, NA=Not Applicable
# display_size: format X/Y
#               For TF X=visible field size, Y max length size
#               For TA X=number of columns, Y=number of rows
#               For SB Not applicable
# label       : short name (used on the HTML form)
# description : longer description of this field
# scope       : S if predefined values are for the entire Codex,
#               P if values can be re-defined at the project level
# required    : 0 a project can decide not to use this bug field
#               1 all projects have to use this bug field
# empty_ok    : 0 this field must always be assigned a value
#               1 empty value (null) is ok
# keep_history: 0 do not keep old field values in the bug_history table
#               1 yes keep the old values in the history table
# special     : 0 process this field as usual
#               1 this field require some special processing
# custom      : 0 this is a CodeX field which semantic (label) cannot be customized
#               1 this field is a custom field which label can be user defined
#
CREATE TABLE bug_field (
  bug_field_id int(11) NOT NULL auto_increment,
  field_name varchar(255) NOT NULL default '',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  custom int(11) NOT NULL default '0',
  value_function varchar(255) default NULL,
  PRIMARY KEY  (bug_field_id),
  KEY idx_bug_field_name (field_name)
) TYPE=MyISAM;

#
# Table structure for table 'bug_field_usage'
#
#
# bug_field_id    : reference to the field id in bug_field
# group_id        : group id this field usage belongs to (if 100 then
#                   this is either a system wide value
#                   or it is the default value for a project field if no
#                   project specific values are specified
# use_it          : 1 the project uses this field, 0 do not use it
# show_on_add     : 1 show this field on the bug add form for non project
#                   members, 0 do not show it.
# show_on_add_members : 1 show this field on the bug add form for project
#                   members with appropriate rigths, 0 do not show it.
# place           : A value indicating in which order the fields appear on
#                   the bug submission screen (lowest first)
# custom_label    : custom field label as defined by the user. NULL if 
#                   it uses the system default label.
# custom_description : custom description as defined by the user.
#                   NULL if it uses the system default description
# custom_display_size : custom size as defined by the user. NULL if it 
#                   uses the system default display size.
# custom_empty_ok : 1 if empty value are allowed for this field. 0 if it
#                   is not. NULL if it uses the system default.
# custom_keep_history : 1 if field changes must be kept in the bug history
#                   table. 0 otherwise. NULL if it uses the system default.
#
# Remark: for all fields declared in bug_field table there must be a
# corresponding entry here (group_id = 100) to define default usage rules.
# For all other groups (real projects) only the fields actually used
# (or once used and then set back to unused) will be stored.
#
CREATE TABLE bug_field_usage (
  bug_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  show_on_add int(11) NOT NULL default '0',
  show_on_add_members int(11) NOT NULL default '0',
  place int(11) default NULL,
  custom_label varchar(255) default NULL,
  custom_description varchar(255) default NULL,
  custom_display_size varchar(255) default NULL,
  custom_empty_ok int(11) default NULL,
  custom_keep_history int(11) default NULL,
  custom_value_function varchar(255) default NULL,
  KEY idx_bug_fu_field_id (bug_field_id),
  KEY idx_bug_fu_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_field_value'
#
#
# bug_field_id    : reference to the field id in bug_field
# group_id        : group id this field value belongs to (if 100 then
#                   this is either a system wide value (see scope above)
#                   or it is the default value for a project field if no
#                   project specific values are specified
# value_id        : the id of the value
#                   0 is reserved for 'Any' and must *never* be stored here
#                   100 is reserved for 'None' and must be stored here
# value           : the text value
# description     : An explanation of the value (not used a lot but...)
# order_id        : number telling at which place in the select box
#                   a value must appear
# status          : A the value is active. It displays in select boxes
#                   H the value is hidden (not shown in select boxes but
#                   it's still here for old bugs using it
#                   P the value is permanent. It means that it is active and
#                   it cannot be changed to hidden by the project even if 
#                   bug field has a 'project' scope (very useful to force
#                   some commonly accepted values to appear in the select
#                   box. The 'None' values are good examples of that)
#
#
CREATE TABLE bug_field_value (
  bug_fv_id int(11) NOT NULL auto_increment,
  bug_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  value text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  PRIMARY KEY  (bug_fv_id),
  KEY idx_bug_fv_field_id (bug_fv_id),
  KEY idx_bug_fv_group_id (group_id),
  KEY idx_bug_fv_value_id (value_id),
  KEY idx_bug_fv_status (status)
) TYPE=MyISAM;

#
# Table structure for table 'bug_file' for bug attachments
#

CREATE TABLE bug_file (
  bug_file_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  description text NOT NULL,
  file longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  PRIMARY KEY  (bug_file_id),
  KEY bug_id_idx (bug_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_filter'
#

CREATE TABLE bug_filter (
  filter_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  sql_clause text NOT NULL,
  is_active int(11) NOT NULL default '0',
  PRIMARY KEY  (filter_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_history'
#

CREATE TABLE bug_history (
  bug_history_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  date int(11) default NULL,
  type int(11) default NULL,
  PRIMARY KEY  (bug_history_id),
  KEY idx_bug_history_bug_id (bug_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_notification'
# Says which user want to receive email notification depending on her role
# and bug update events
#

CREATE TABLE bug_notification (
  user_id int(11) NOT NULL default '0',
  role_id int(11) NOT NULL default '0',
  event_id int(11) NOT NULL default '0',
  notify int(11) NOT NULL default '1',
  KEY user_id_idx (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_notification_event'
#  Rk: rank is an integer which allows to present the information
#     in a given order on the screen.
#

CREATE TABLE bug_notification_event (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY event_id_idx (event_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_notification_role'
#

CREATE TABLE bug_notification_role (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY role_id_idx (role_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_report'
#
# Notes: 
# - scope='S' means a bug report available to all projects
# (defined by CodeX Site administrators, group_id =100)
# - scope='P' means a bug report available to all project members
# of project group_id (defined by project admin)
# - scope='I' means a personal (individual) bug report only visible 
# and manageable by the owner. (defined by any project members)
#
CREATE TABLE bug_report (
  report_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '100',
  user_id int(11) NOT NULL default '100',
  name varchar(80) default NULL,
  description varchar(255) default NULL,
  scope char(1) NOT NULL default 'I',
  PRIMARY KEY  (report_id),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY scope_idx (scope)
) TYPE=MyISAM;

#
# Table structure for table 'bug_report_field'
#
# field_name      : name of the field used in this report (as defined in
#                   the 'field_name' column of bug_field table
# show_on_query   : 1 show this field on the bug query form as a selection
#                   criteria.
# show_on_result  : 1 show this field on the bug query form as a column in
#                   the result list, 0 do not show it.
# place_query     : A value indicating in which order the fields appear on
#                   the bug search criteria (lowest first)
# place_result    : A value indicating in which order the fields appear on
#                   the bug search results table (lowest first)
# col_width       : A %age of the total window size that defines the width
#                   of the column in the report.
#
CREATE TABLE bug_report_field (
  report_id int(11) NOT NULL default '100',
  field_name varchar(255) default NULL,
  show_on_query int(11) default NULL,
  show_on_result int(11) default NULL,
  place_query int(11) default NULL,
  place_result int(11) default NULL,
  col_width int(11) default NULL,
  KEY profile_id_idx (report_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_task_dependencies'
#

CREATE TABLE bug_task_dependencies (
  bug_depend_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  is_dependent_on_task_id int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_depend_id),
  KEY idx_bug_task_dependencies_bug_id (bug_id),
  KEY idx_bug_task_is_dependent_on_task_id (is_dependent_on_task_id)
) TYPE=MyISAM;

#
# Table structure for table 'bug_watcher'
# Allow a user to receive the same notification as a list
# of other people
#

CREATE TABLE bug_watcher (
  user_id int(11) NOT NULL default '0',
  watchee_id int(11) NOT NULL default '0',
  KEY user_id_idx (user_id),
  KEY watchee_id_idx (watchee_id)
) TYPE=MyISAM;

#
# Table structure for table 'db_images'
#

CREATE TABLE db_images (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  description text NOT NULL,
  bin_data longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  width int(11) NOT NULL default '0',
  height int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idx_db_images_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'doc_data'
#

CREATE TABLE doc_data (
  docid int(11) NOT NULL auto_increment,
  stateid int(11) NOT NULL default '0',
  title varchar(255) NOT NULL default '',
  data longblob NOT NULL,
  updatedate int(11) NOT NULL default '0',
  createdate int(11) NOT NULL default '0',
  created_by int(11) NOT NULL default '0',
  doc_group int(11) NOT NULL default '0',
  description text,
  restricted_access INT UNSIGNED NOT NULL DEFAULT 0,
  filename text,
  filesize INT UNSIGNED NOT NULL DEFAULT 0,
  filetype text,
  PRIMARY KEY  (docid),
  KEY idx_doc_group_doc_group (doc_group)
) TYPE=MyISAM;

#
# Table structure for table 'doc_groups'
#

CREATE TABLE doc_groups (
  doc_group int(12) NOT NULL auto_increment,
  groupname varchar(255) NOT NULL default '',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (doc_group),
  KEY idx_doc_groups_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'doc_states'
#

CREATE TABLE doc_states (
  stateid int(11) NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (stateid)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'filemodule_monitor'
#

CREATE TABLE filemodule_monitor (
  filemodule_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  KEY idx_filemodule_monitor_id (filemodule_id)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'forum_agg_msg_count'
#

CREATE TABLE forum_agg_msg_count (
  group_forum_id int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0',
  PRIMARY KEY  (group_forum_id)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'forum_saved_place'
#

CREATE TABLE forum_saved_place (
  saved_place_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  forum_id int(11) NOT NULL default '0',
  save_date int(11) NOT NULL default '0',
  PRIMARY KEY  (saved_place_id)
) TYPE=MyISAM;

#
# Table structure for table 'forum_thread_id'
#

CREATE TABLE forum_thread_id (
  thread_id int(11) NOT NULL auto_increment,
  PRIMARY KEY  (thread_id)
) TYPE=MyISAM;

#
# Table structure for table 'foundry_data'
#

CREATE TABLE foundry_data (
  foundry_id int(11) NOT NULL auto_increment,
  freeform1_html text,
  freeform2_html text,
  sponsor1_html text,
  sponsor2_html text,
  guide_image_id int(11) NOT NULL default '0',
  logo_image_id int(11) NOT NULL default '0',
  trove_categories text,
  PRIMARY KEY  (foundry_id)
) TYPE=MyISAM;

#
# Table structure for table 'foundry_news'
#

CREATE TABLE foundry_news (
  foundry_news_id int(11) NOT NULL auto_increment,
  foundry_id int(11) NOT NULL default '0',
  news_id int(11) NOT NULL default '0',
  approve_date int(11) NOT NULL default '0',
  is_approved int(11) NOT NULL default '0',
  PRIMARY KEY  (foundry_news_id),
  KEY idx_foundry_news_foundry (foundry_id),
  KEY idx_foundry_news_foundry_approved_date (foundry_id,is_approved,approve_date),
  KEY idx_foundry_news_foundry_approved (foundry_id,is_approved)
) TYPE=MyISAM;

#
# Table structure for table 'foundry_preferred_projects'
#

CREATE TABLE foundry_preferred_projects (
  foundry_project_id int(11) NOT NULL auto_increment,
  foundry_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  PRIMARY KEY  (foundry_project_id),
  KEY idx_foundry_project_group (group_id),
  KEY idx_foundry_project_group_rank (group_id,rank)
) TYPE=MyISAM;

#
# Table structure for table 'foundry_projects'
#

CREATE TABLE foundry_projects (
  id int(11) NOT NULL auto_increment,
  foundry_id int(11) NOT NULL default '0',
  project_id int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idx_foundry_projects_foundry (foundry_id)
) TYPE=MyISAM;

#
# Table structure for table 'frs_dlstats_agg'
#

CREATE TABLE frs_dlstats_agg (
  file_id int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  downloads_http int(11) NOT NULL default '0',
  downloads_ftp int(11) NOT NULL default '0',
  KEY file_id_idx (file_id),
  KEY day_idx (day),
  KEY downloads_http_idx (downloads_http),
  KEY downloads_ftp_idx (downloads_ftp)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'frs_dlstats_filetotal_agg'
#

CREATE TABLE frs_dlstats_filetotal_agg (
  file_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_fid (file_id)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'frs_dlstats_grouptotal_agg'
#

CREATE TABLE frs_dlstats_grouptotal_agg (
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_gid (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'frs_file'
#

CREATE TABLE frs_file (
  file_id int(11) NOT NULL auto_increment,
  filename text,
  release_id int(11) NOT NULL default '0',
  type_id int(11) NOT NULL default '0',
  processor_id int(11) NOT NULL default '0',
  release_time int(11) NOT NULL default '0',
  file_size int(11) NOT NULL default '0',
  post_date int(11) NOT NULL default '0',
  PRIMARY KEY  (file_id),
  KEY idx_frs_file_release_id (release_id),
  KEY idx_frs_file_type (type_id),
  KEY idx_frs_file_date (post_date),
  KEY idx_frs_file_processor (processor_id),
  KEY idx_frs_file_name (filename(45))
) TYPE=MyISAM;

#
# Table structure for table 'frs_filetype'
#

CREATE TABLE frs_filetype (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (type_id)
) TYPE=MyISAM;

#
# Table structure for table 'frs_package'
#

CREATE TABLE frs_package (
  package_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  name text,
  status_id int(11) NOT NULL default '0',
  PRIMARY KEY  (package_id),
  KEY idx_package_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'frs_processor'
#

CREATE TABLE frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (processor_id)
) TYPE=MyISAM;

#
# Table structure for table 'frs_release'
#

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
) TYPE=MyISAM;

#
# Table structure for table 'frs_status'
#

CREATE TABLE frs_status (
  status_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (status_id)
) TYPE=MyISAM;

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
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (day)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'group_type'
#

CREATE TABLE group_type (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (type_id)
) TYPE=MyISAM;

#
# Table structure for table 'groups'
#

CREATE TABLE groups (
  group_id int(11) NOT NULL auto_increment,
  group_name varchar(40) default NULL,
  is_public int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  unix_group_name varchar(30) NOT NULL default '',
  unix_box varchar(20) NOT NULL default 'shell1',
  http_domain varchar(80) default NULL,
  short_description varchar(255) default NULL,
  cvs_box varchar(20) NOT NULL default 'cvs1',
  license varchar(16) default NULL,
  register_purpose text,
  required_software text,
  patents_ips text,
  other_comments text,
  license_other text,
  register_time int(11) NOT NULL default '0',
  rand_hash text,
  new_bug_address text NOT NULL,
  new_patch_address text NOT NULL,
  new_support_address text NOT NULL,
  type int(11) NOT NULL default '1',
  send_all_bugs int(11) NOT NULL default '0',
  send_all_patches int(11) NOT NULL default '0',
  send_all_support int(11) NOT NULL default '0',
  bug_preamble text NOT NULL,
  support_preamble text NOT NULL,
  patch_preamble text NOT NULL,
  pm_preamble text NOT NULL,
  xrx_export_ettm int(11) NOT NULL default '0',
  project_type int(11) NOT NULL default '0',
  bug_allow_anon int(11) NOT NULL default '1',
  cvs_tracker int(11)   NOT NULL default '1',
  cvs_events_mailing_list varchar(64) binary DEFAULT NULL,
  cvs_events_mailing_header varchar(64) binary DEFAULT NULL,
  cvs_preamble text NOT NULL,
  PRIMARY KEY  (group_id),
  KEY idx_groups_status (status),
  KEY idx_groups_public (is_public),
  KEY idx_groups_unix (unix_group_name),
  KEY idx_groups_type (type)
) TYPE=MyISAM;

#
# Table structure for table 'image'
#

CREATE TABLE image (
  image_id int(11) NOT NULL auto_increment,
  image_category int(11) NOT NULL default '1',
  image_type varchar(40) NOT NULL default '',
  image_data blob,
  group_id int(11) NOT NULL default '0',
  image_bytes int(11) NOT NULL default '0',
  image_caption text,
  organization_id int(11) NOT NULL default '0',
  PRIMARY KEY  (image_id),
  KEY image_category_idx (image_category),
  KEY image_type_idx (image_type),
  KEY group_id_idx (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'intel_agreement'
#

CREATE TABLE intel_agreement (
  user_id int(11) NOT NULL default '0',
  message text,
  is_approved int(11) NOT NULL default '0',
  PRIMARY KEY  (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'mail_group_list'
#

CREATE TABLE mail_group_list (
  group_list_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  list_name text,
  is_public int(11) NOT NULL default '0',
  password varchar(16) default NULL,
  list_admin int(11) NOT NULL default '0',
  status int(11) NOT NULL default '0',
  description text,
  PRIMARY KEY  (group_list_id),
  KEY idx_mail_group_list_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'mailaliases'
#

CREATE TABLE mailaliases (
  mailaliases_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  domain varchar(80) default NULL,
  user_name varchar(20) default NULL,
  email_forward varchar(255) default NULL,
  PRIMARY KEY  (mailaliases_id)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'patch'
#

CREATE TABLE patch (
  patch_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  patch_status_id int(11) NOT NULL default '0',
  patch_category_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  assigned_to int(11) NOT NULL default '0',
  open_date int(11) NOT NULL default '0',
  summary text,
  code longblob,
  close_date int(11) NOT NULL default '0',
  filename varchar(255) NOT NULL default '',
  filesize varchar(50) NOT NULL default '',
  filetype varchar(50) NOT NULL default '',
  PRIMARY KEY  (patch_id),
  KEY idx_patch_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'patch_category'
#

CREATE TABLE patch_category (
  patch_category_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  category_name text NOT NULL,
  PRIMARY KEY  (patch_category_id),
  KEY idx_patch_group_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'patch_history'
#

CREATE TABLE patch_history (
  patch_history_id int(11) NOT NULL auto_increment,
  patch_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  date int(11) default NULL,
  PRIMARY KEY  (patch_history_id),
  KEY idx_patch_history_patch_id (patch_id)
) TYPE=MyISAM;

#
# Table structure for table 'patch_status'
#

CREATE TABLE patch_status (
  patch_status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY  (patch_status_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_job'
#

CREATE TABLE people_job (
  job_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  created_by int(11) NOT NULL default '0',
  title text,
  description text,
  date int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '0',
  category_id int(11) NOT NULL default '0',
  PRIMARY KEY  (job_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_job_category'
#

CREATE TABLE people_job_category (
  category_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (category_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_job_inventory'
#

CREATE TABLE people_job_inventory (
  job_inventory_id int(11) NOT NULL auto_increment,
  job_id int(11) NOT NULL default '0',
  skill_id int(11) NOT NULL default '0',
  skill_level_id int(11) NOT NULL default '0',
  skill_year_id int(11) NOT NULL default '0',
  PRIMARY KEY  (job_inventory_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_job_status'
#

CREATE TABLE people_job_status (
  status_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (status_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_skill'
#

CREATE TABLE people_skill (
  skill_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (skill_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_skill_inventory'
#

CREATE TABLE people_skill_inventory (
  skill_inventory_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  skill_id int(11) NOT NULL default '0',
  skill_level_id int(11) NOT NULL default '0',
  skill_year_id int(11) NOT NULL default '0',
  PRIMARY KEY  (skill_inventory_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_skill_level'
#

CREATE TABLE people_skill_level (
  skill_level_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (skill_level_id)
) TYPE=MyISAM;

#
# Table structure for table 'people_skill_year'
#

CREATE TABLE people_skill_year (
  skill_year_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (skill_year_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_assigned_to'
#

CREATE TABLE project_assigned_to (
  project_assigned_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  assigned_to_id int(11) NOT NULL default '0',
  PRIMARY KEY  (project_assigned_id),
  KEY idx_project_assigned_to_task_id (project_task_id),
  KEY idx_project_assigned_to_assigned_to (assigned_to_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_counts_tmp'
#

CREATE TABLE project_counts_tmp (
  group_id int(11) default NULL,
  type text,
  count float(8,5) default NULL
) TYPE=MyISAM;

#
# Table structure for table 'project_counts_weekly_tmp'
#

CREATE TABLE project_counts_weekly_tmp (
  group_id int(11) default NULL,
  type text,
  count float(8,5) default NULL
) TYPE=MyISAM;

#
# Table structure for table 'project_dependencies'
#

CREATE TABLE project_dependencies (
  project_depend_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  is_dependent_on_task_id int(11) NOT NULL default '0',
  PRIMARY KEY  (project_depend_id),
  KEY idx_project_dependencies_task_id (project_task_id),
  KEY idx_project_is_dependent_on_task_id (is_dependent_on_task_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_group_list'
#

CREATE TABLE project_group_list (
  group_project_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  project_name text NOT NULL,
  is_public int(11) NOT NULL default '0',
  description text,
  order_id int(11) NOT NULL default '0',
  PRIMARY KEY  (group_project_id),
  KEY idx_project_group_list_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_history'
#

CREATE TABLE project_history (
  project_history_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  PRIMARY KEY  (project_history_id),
  KEY idx_project_history_task_id (project_task_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_metric'
#

CREATE TABLE project_metric (
  ranking int(11) NOT NULL auto_increment,
  percentile float(8,2) default NULL,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (ranking),
  KEY idx_project_metric_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_metric_tmp1'
#

CREATE TABLE project_metric_tmp1 (
  ranking int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  value float(8,5) default NULL,
  PRIMARY KEY  (ranking)
) TYPE=MyISAM;

#
# Table structure for table 'project_metric_weekly_tmp1'
#

CREATE TABLE project_metric_weekly_tmp1 (
  ranking int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  value float(8,5) default NULL,
  PRIMARY KEY  (ranking)
) TYPE=MyISAM;

#
# Table structure for table 'project_status'
#

CREATE TABLE project_status (
  status_id int(11) NOT NULL auto_increment,
  status_name text NOT NULL,
  PRIMARY KEY  (status_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_task'
#

CREATE TABLE project_task (
  project_task_id int(11) NOT NULL auto_increment,
  group_project_id int(11) NOT NULL default '0',
  summary text NOT NULL,
  details text NOT NULL,
  percent_complete int(11) NOT NULL default '0',
  priority int(11) NOT NULL default '0',
  hours float(10,2) NOT NULL default '0.00',
  start_date int(11) NOT NULL default '0',
  end_date int(11) NOT NULL default '0',
  created_by int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '0',
  PRIMARY KEY  (project_task_id),
  KEY idx_project_task_group_project_id (group_project_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_weekly_metric'
#

CREATE TABLE project_weekly_metric (
  ranking int(11) NOT NULL auto_increment,
  percentile float(8,2) default NULL,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (ranking),
  KEY idx_project_metric_weekly_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'session'
#

CREATE TABLE session (
  user_id int(11) NOT NULL default '0',
  session_hash char(32) NOT NULL default '',
  ip_addr char(15) NOT NULL default '',
  time int(11) NOT NULL default '0',
  PRIMARY KEY  (session_hash),
  KEY idx_session_user_id (user_id),
  KEY time_idx (time),
  KEY idx_session_time (time)
) TYPE=MyISAM;

#
# Table structure for table 'snippet'
#

CREATE TABLE snippet (
  snippet_id int(11) NOT NULL auto_increment,
  created_by int(11) NOT NULL default '0',
  name text,
  description text,
  type int(11) NOT NULL default '0',
  language int(11) NOT NULL default '0',
  license text NOT NULL,
  category int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_id),
  KEY idx_snippet_language (language),
  KEY idx_snippet_category (category)
) TYPE=MyISAM;

#
# Table structure for table 'snippet_package'
#

CREATE TABLE snippet_package (
  snippet_package_id int(11) NOT NULL auto_increment,
  created_by int(11) NOT NULL default '0',
  name text,
  description text,
  category int(11) NOT NULL default '0',
  language int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_package_id),
  KEY idx_snippet_package_language (language),
  KEY idx_snippet_package_category (category)
) TYPE=MyISAM;

#
# Table structure for table 'snippet_package_item'
#

CREATE TABLE snippet_package_item (
  snippet_package_item_id int(11) NOT NULL auto_increment,
  snippet_package_version_id int(11) NOT NULL default '0',
  snippet_version_id int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_package_item_id),
  KEY idx_snippet_package_item_pkg_ver (snippet_package_version_id)
) TYPE=MyISAM;

#
# Table structure for table 'snippet_package_version'
#

CREATE TABLE snippet_package_version (
  snippet_package_version_id int(11) NOT NULL auto_increment,
  snippet_package_id int(11) NOT NULL default '0',
  changes text,
  version text,
  submitted_by int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_package_version_id),
  KEY idx_snippet_package_version_pkg_id (snippet_package_id)
) TYPE=MyISAM;

#
# Table structure for table 'snippet_version'
#

CREATE TABLE snippet_version (
  snippet_version_id int(11) NOT NULL auto_increment,
  snippet_id int(11) NOT NULL default '0',
  changes text,
  version text,
  submitted_by int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  code longblob,
  filename varchar(255) NOT NULL default '',
  filesize varchar(50) NOT NULL default '',
  filetype varchar(50) NOT NULL default '',
  PRIMARY KEY  (snippet_version_id),
  KEY idx_snippet_version_snippet_id (snippet_id)
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_logo_by_day'
#

CREATE TABLE stats_agg_logo_by_day (
  day int(11) default NULL,
  count int(11) default NULL
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_logo_by_group'
#

CREATE TABLE stats_agg_logo_by_group (
  day int(11) default NULL,
  group_id int(11) default NULL,
  count int(11) default NULL
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_pages_by_browser'
#

CREATE TABLE stats_agg_pages_by_browser (
  browser varchar(8) default NULL,
  count int(11) default NULL
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_pages_by_day'
#

CREATE TABLE stats_agg_pages_by_day (
  day int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0',
  KEY idx_pages_by_day_day (day)
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_pages_by_day_old'
#

CREATE TABLE stats_agg_pages_by_day_old (
  day int(11) default NULL,
  count int(11) default NULL
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_site_by_day'
#

CREATE TABLE stats_agg_site_by_day (
  day int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Table structure for table 'stats_agg_site_by_group'
#

CREATE TABLE stats_agg_site_by_group (
  day int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Table structure for table 'stats_agr_filerelease'
#

CREATE TABLE stats_agr_filerelease (
  filerelease_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_fid (filerelease_id),
  KEY idx_stats_agr_tmp_gid (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'stats_agr_project'
#

CREATE TABLE stats_agr_project (
  group_id int(11) NOT NULL default '0',
  group_ranking int(11) NOT NULL default '0',
  group_metric float(8,5) NOT NULL default '0.00000',
  developers smallint(6) NOT NULL default '0',
  file_releases smallint(6) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  logo_views int(11) NOT NULL default '0',
  msg_posted smallint(6) NOT NULL default '0',
  msg_uniq_auth smallint(6) NOT NULL default '0',
  bugs_opened smallint(6) NOT NULL default '0',
  bugs_closed smallint(6) NOT NULL default '0',
  support_opened smallint(6) NOT NULL default '0',
  support_closed smallint(6) NOT NULL default '0',
  patches_opened smallint(6) NOT NULL default '0',
  patches_closed smallint(6) NOT NULL default '0',
  tasks_opened smallint(6) NOT NULL default '0',
  tasks_closed smallint(6) NOT NULL default '0',
  help_requests smallint(6) NOT NULL default '0',
  cvs_checkouts smallint(6) NOT NULL default '0',
  cvs_commits smallint(6) NOT NULL default '0',
  cvs_adds smallint(6) NOT NULL default '0',
  KEY idx_project_agr_log_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'stats_ftp_downloads'
#

CREATE TABLE stats_ftp_downloads (
  day int(11) NOT NULL default '0',
  filerelease_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_ftpdl_day (day),
  KEY idx_ftpdl_fid (filerelease_id),
  KEY idx_ftpdl_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'stats_http_downloads'
#

CREATE TABLE stats_http_downloads (
  day int(11) NOT NULL default '0',
  filerelease_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_httpdl_day (day),
  KEY idx_httpdl_fid (filerelease_id),
  KEY idx_httpdl_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'stats_project'
#

CREATE TABLE stats_project (
  month int(11) NOT NULL default '0',
  week int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  group_ranking int(11) NOT NULL default '0',
  group_metric float(8,5) NOT NULL default '0.00000',
  developers smallint(6) NOT NULL default '0',
  file_releases smallint(6) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  subdomain_views int(11) NOT NULL default '0',
  msg_posted smallint(6) NOT NULL default '0',
  msg_uniq_auth smallint(6) NOT NULL default '0',
  bugs_opened smallint(6) NOT NULL default '0',
  bugs_closed smallint(6) NOT NULL default '0',
  support_opened smallint(6) NOT NULL default '0',
  support_closed smallint(6) NOT NULL default '0',
  patches_opened smallint(6) NOT NULL default '0',
  patches_closed smallint(6) NOT NULL default '0',
  tasks_opened smallint(6) NOT NULL default '0',
  tasks_closed smallint(6) NOT NULL default '0',
  help_requests smallint(6) NOT NULL default '0',
  cvs_checkouts smallint(6) NOT NULL default '0',
  cvs_commits smallint(6) NOT NULL default '0',
  cvs_adds smallint(6) NOT NULL default '0',
  KEY idx_project_log_group (group_id),
  KEY idx_archive_project_month (month),
  KEY idx_archive_project_week (week),
  KEY idx_archive_project_day (day),
  KEY idx_archive_project_monthday (month,day)
) TYPE=MyISAM;

#
# Table structure for table 'stats_project_tmp'
#

CREATE TABLE stats_project_tmp (
  month int(11) NOT NULL default '0',
  week int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  group_ranking int(11) NOT NULL default '0',
  group_metric float(8,5) NOT NULL default '0.00000',
  developers smallint(6) NOT NULL default '0',
  file_releases smallint(6) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  subdomain_views int(11) NOT NULL default '0',
  msg_posted smallint(6) NOT NULL default '0',
  msg_uniq_auth smallint(6) NOT NULL default '0',
  bugs_opened smallint(6) NOT NULL default '0',
  bugs_closed smallint(6) NOT NULL default '0',
  support_opened smallint(6) NOT NULL default '0',
  support_closed smallint(6) NOT NULL default '0',
  patches_opened smallint(6) NOT NULL default '0',
  patches_closed smallint(6) NOT NULL default '0',
  tasks_opened smallint(6) NOT NULL default '0',
  tasks_closed smallint(6) NOT NULL default '0',
  help_requests smallint(6) NOT NULL default '0',
  cvs_checkouts smallint(6) NOT NULL default '0',
  cvs_commits smallint(6) NOT NULL default '0',
  cvs_adds smallint(6) NOT NULL default '0',
  KEY idx_project_log_group (group_id),
  KEY idx_project_stats_day (day),
  KEY idx_project_stats_week (week),
  KEY idx_project_stats_month (month)
) TYPE=MyISAM;

#
# Table structure for table 'stats_site'
#

CREATE TABLE stats_site (
  month int(11) NOT NULL default '0',
  week int(11) NOT NULL default '0',
  day int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  subdomain_views int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  uniq_users int(11) NOT NULL default '0',
  sessions int(11) NOT NULL default '0',
  total_users int(11) NOT NULL default '0',
  new_users int(11) NOT NULL default '0',
  new_projects int(11) NOT NULL default '0',
  KEY idx_stats_site_month (month),
  KEY idx_stats_site_week (week),
  KEY idx_stats_site_day (day),
  KEY idx_stats_site_monthday (month,day)
) TYPE=MyISAM;

#
# Table structure for table 'support'
#

CREATE TABLE support (
  support_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  support_status_id int(11) NOT NULL default '0',
  support_category_id int(11) NOT NULL default '0',
  priority int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  assigned_to int(11) NOT NULL default '0',
  open_date int(11) NOT NULL default '0',
  summary text,
  close_date int(11) NOT NULL default '0',
  PRIMARY KEY  (support_id),
  KEY idx_support_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'support_canned_responses'
#

CREATE TABLE support_canned_responses (
  support_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  title text,
  body text,
  PRIMARY KEY  (support_canned_id),
  KEY idx_support_canned_response_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'support_category'
#

CREATE TABLE support_category (
  support_category_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  category_name text NOT NULL,
  PRIMARY KEY  (support_category_id),
  KEY idx_support_group_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'support_history'
#

CREATE TABLE support_history (
  support_history_id int(11) NOT NULL auto_increment,
  support_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  date int(11) default NULL,
  PRIMARY KEY  (support_history_id),
  KEY idx_support_history_support_id (support_id)
) TYPE=MyISAM;

#
# Table structure for table 'support_messages'
#

CREATE TABLE support_messages (
  support_message_id int(11) NOT NULL auto_increment,
  support_id int(11) NOT NULL default '0',
  from_email text,
  date int(11) NOT NULL default '0',
  body text,
  PRIMARY KEY  (support_message_id),
  KEY idx_support_messages_support_id (support_id)
) TYPE=MyISAM;

#
# Table structure for table 'support_status'
#

CREATE TABLE support_status (
  support_status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY  (support_status_id)
) TYPE=MyISAM;

#
# Table structure for table 'survey_question_types'
#

CREATE TABLE survey_question_types (
  id int(11) NOT NULL auto_increment,
  type text NOT NULL,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

#
# Table structure for table 'survey_questions'
#

CREATE TABLE survey_questions (
  question_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  question text NOT NULL,
  question_type int(11) NOT NULL default '0',
  PRIMARY KEY  (question_id),
  KEY idx_survey_questions_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'survey_rating_aggregate'
#

CREATE TABLE survey_rating_aggregate (
  type int(11) NOT NULL default '0',
  id int(11) NOT NULL default '0',
  response float NOT NULL default '0',
  count int(11) NOT NULL default '0',
  KEY idx_survey_rating_aggregate_type_id (type,id)
) TYPE=MyISAM;

#
# Table structure for table 'survey_rating_response'
#

CREATE TABLE survey_rating_response (
  user_id int(11) NOT NULL default '0',
  type int(11) NOT NULL default '0',
  id int(11) NOT NULL default '0',
  response int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  KEY idx_survey_rating_responses_user_type_id (user_id,type,id),
  KEY idx_survey_rating_responses_type_id (type,id)
) TYPE=MyISAM;

#
# Table structure for table 'survey_responses'
#

CREATE TABLE survey_responses (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  survey_id int(11) NOT NULL default '0',
  question_id int(11) NOT NULL default '0',
  response text NOT NULL,
  date int(11) NOT NULL default '0',
  KEY idx_survey_responses_user_survey (user_id,survey_id),
  KEY idx_survey_responses_user_survey_question (user_id,survey_id,question_id),
  KEY idx_survey_responses_survey_question (survey_id,question_id),
  KEY idx_survey_responses_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'surveys'
#

CREATE TABLE surveys (
  survey_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  survey_title text NOT NULL,
  survey_questions text NOT NULL,
  is_active int(11) NOT NULL default '1',
  is_anonymous int(11) NOT NULL default '0',
  PRIMARY KEY  (survey_id),
  KEY idx_surveys_group (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'theme_prefs'
#

CREATE TABLE theme_prefs (
  user_id int(11) NOT NULL default '0',
  user_theme int(8) NOT NULL default '0',
  BODY_font char(80) default '',
  BODY_size char(5) default '',
  TITLEBAR_font char(80) default '',
  TITLEBAR_size char(5) default '',
  COLOR_TITLEBAR_BACK char(7) default '',
  COLOR_LTBACK1 char(7) default '',
  PRIMARY KEY  (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'themes'
#

CREATE TABLE themes (
  theme_id int(11) NOT NULL auto_increment,
  dirname varchar(80) default NULL,
  fullname varchar(80) default NULL,
  PRIMARY KEY  (theme_id)
) TYPE=MyISAM;

#
# Table structure for table 'tmp_projs_releases_tmp'
#

CREATE TABLE tmp_projs_releases_tmp (
  year int(11) NOT NULL default '0',
  month int(11) NOT NULL default '0',
  total_proj int(11) NOT NULL default '0',
  total_releases int(11) NOT NULL default '0'
) TYPE=MyISAM;

#
# Table structure for table 'top_group'
#

CREATE TABLE top_group (
  group_id int(11) NOT NULL default '0',
  group_name varchar(40) default NULL,
  downloads_all int(11) NOT NULL default '0',
  rank_downloads_all int(11) NOT NULL default '0',
  rank_downloads_all_old int(11) NOT NULL default '0',
  downloads_week int(11) NOT NULL default '0',
  rank_downloads_week int(11) NOT NULL default '0',
  rank_downloads_week_old int(11) NOT NULL default '0',
  userrank int(11) NOT NULL default '0',
  rank_userrank int(11) NOT NULL default '0',
  rank_userrank_old int(11) NOT NULL default '0',
  forumposts_week int(11) NOT NULL default '0',
  rank_forumposts_week int(11) NOT NULL default '0',
  rank_forumposts_week_old int(11) NOT NULL default '0',
  pageviews_proj int(11) NOT NULL default '0',
  rank_pageviews_proj int(11) NOT NULL default '0',
  rank_pageviews_proj_old int(11) NOT NULL default '0',
  KEY rank_downloads_all_idx (rank_downloads_all),
  KEY rank_downloads_week_idx (rank_downloads_week),
  KEY rank_userrank_idx (rank_userrank),
  KEY rank_forumposts_week_idx (rank_forumposts_week),
  KEY pageviews_proj_idx (pageviews_proj)
) TYPE=MyISAM;

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
  PRIMARY KEY  (trove_cat_id),
  KEY parent_idx (parent),
  KEY root_parent_idx (root_parent),
  KEY version_idx (version)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'trove_treesums'
#

CREATE TABLE trove_treesums (
  trove_treesums_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) NOT NULL default '0',
  limit_1 int(11) NOT NULL default '0',
  subprojects int(11) NOT NULL default '0',
  PRIMARY KEY  (trove_treesums_id)
) TYPE=MyISAM;

#
# Table structure for table 'user'
#

CREATE TABLE user (
  user_id int(11) NOT NULL auto_increment,
  user_name text NOT NULL,
  email text NOT NULL,
  user_pw varchar(32) NOT NULL default '',
  realname varchar(32) NOT NULL default '',
  register_purpose text,
  status char(1) NOT NULL default 'A',
  shell varchar(20) NOT NULL default '/bin/bash',
  unix_pw varchar(40) NOT NULL default '',
  unix_status char(1) NOT NULL default 'N',
  unix_uid int(11) NOT NULL default '0',
  unix_box varchar(10) NOT NULL default 'shell1',
  add_date int(11) NOT NULL default '0',
  confirm_hash varchar(32) default NULL,
  mail_siteupdates int(11) NOT NULL default '0',
  mail_va int(11) NOT NULL default '0',
  sticky_login int(11) NOT NULL default '0',
  authorized_keys text,
  email_new text,
  people_view_skills int(11) NOT NULL default '0',
  people_resume text NOT NULL,
  timezone varchar(64) default 'GMT',
  windows_pw varchar(80) NOT NULL default '',
  fontsize INT UNSIGNED NOT NULL DEFAULT 0,
  theme varchar(50),
  PRIMARY KEY  (user_id),
  KEY idx_user_user (status)
) TYPE=MyISAM;

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
) TYPE=MyISAM;

#
# Table structure for table 'user_diary'
#

CREATE TABLE user_diary (
  id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  date_posted int(11) NOT NULL default '0',
  summary text,
  details text,
  PRIMARY KEY  (id),
  KEY idx_user_diary_user_date (user_id,date_posted),
  KEY idx_user_diary_date (date_posted),
  KEY idx_user_diary_user (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'user_diary_monitor'
#

CREATE TABLE user_diary_monitor (
  monitor_id int(11) NOT NULL auto_increment,
  user_monitored int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY  (monitor_id),
  KEY idx_user_diary_monitor_user (user_id)
) TYPE=MyISAM;

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
  doc_flags int(11) NOT NULL default '0',
  PRIMARY KEY  (user_group_id),
  KEY idx_user_group_user_id (user_id),
  KEY idx_user_group_group_id (group_id),
  KEY bug_flags_idx (bug_flags),
  KEY forum_flags_idx (forum_flags),
  KEY project_flags_idx (project_flags),
  KEY admin_flags_idx (admin_flags)
) TYPE=MyISAM;

#
# Table structure for table 'user_metric0'
#

CREATE TABLE user_metric0 (
  ranking int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000',
  percentile float(10,8) NOT NULL default '0.00000000',
  importance_factor float(10,8) NOT NULL default '0.00000000',
  PRIMARY KEY  (ranking),
  KEY idx_user_metric0_user_id (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'user_metric1'
#

CREATE TABLE user_metric1 (
  ranking int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000',
  percentile float(10,8) NOT NULL default '0.00000000',
  importance_factor float(10,8) NOT NULL default '0.00000000',
  PRIMARY KEY  (ranking)
) TYPE=MyISAM;

#
# Table structure for table 'user_metric_tmp1_1'
#

CREATE TABLE user_metric_tmp1_1 (
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000'
) TYPE=MyISAM;

#
# Table structure for table 'user_preferences'
#

CREATE TABLE user_preferences (
  user_id int(11) NOT NULL default '0',
  preference_name varchar(255) NOT NULL default '',
  preference_value text,
  PRIMARY KEY  (user_id,preference_name)
) TYPE=MyISAM;

#
# Table structure for table 'user_ratings'
#

CREATE TABLE user_ratings (
  rated_by int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  rate_field int(11) NOT NULL default '0',
  rating int(11) NOT NULL default '0',
  KEY idx_user_ratings_rated_by (rated_by),
  KEY idx_user_ratings_user_id (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'user_trust_metric'
#

CREATE TABLE user_trust_metric (
  ranking int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000',
  percentile float(10,8) NOT NULL default '0.00000000',
  importance_factor float(10,8) NOT NULL default '0.00000000',
  PRIMARY KEY  (ranking)
) TYPE=MyISAM;

#
# Table structure for table 'doc_log'
#

CREATE TABLE doc_log (
  user_id int(11) NOT NULL default '0',
  docid int(11) NOT NULL default '0',
  time int(11) NOT NULL default '0',
  KEY all_idx (user_id,docid),
  KEY time_idx (time),
  KEY docid_idx (docid)
) TYPE=MyISAM;

#
# Table structure for table 'project_cc' for carbon-copied people
# on task email notification
#

CREATE TABLE project_cc (
  project_cc_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  comment text NOT NULL,
  date int(11) NOT NULL default '0',
  PRIMARY KEY  (project_cc_id),
  KEY project_id_idx (project_task_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_watcher'
# Allow a user to receive the same notification as a list
# of other people
#

CREATE TABLE project_watcher (
  user_id int(11) NOT NULL default '0',
  watchee_id int(11) NOT NULL default '0',
  KEY user_id_idx (user_id),
  KEY watchee_id_idx (watchee_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_notification'
# Says which user want to receive email notification depending on her role
# and task update events
#

CREATE TABLE project_notification (
  user_id int(11) NOT NULL default '0',
  role_id int(11) NOT NULL default '0',
  event_id int(11) NOT NULL default '0',
  notify int(11) NOT NULL default '1',
  KEY user_id_idx (user_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_notification_event'
#  Rk: rank is an integer which allows to present the information
#     in a given order on the screen.
#

CREATE TABLE project_notification_event (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY event_id_idx (event_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_notification_role'
#

CREATE TABLE project_notification_role (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY role_id_idx (role_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_file' for task attachments
#

CREATE TABLE project_file (
  project_file_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  date int(11) NOT NULL default '0',
  description text NOT NULL,
  file longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  PRIMARY KEY  (project_file_id),
  KEY project_task_id_idx (project_task_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_field'
#
# field_name  : the name of the field (must be indentical to the
#               column name in the task table
# display_type: TF= text field, TA=text area, SB=Select Box, NA=Not Applicable
# display_size: format X/Y
#               For TF X=visible field size, Y max length size
#               For TA X=number of columns, Y=number of rows
#               For SB Not applicable
# label       : short name (used on the HTML form)
# description : longer description of this field
# scope       : S if predefined values are for the entire Codex,
#               P if values can be re-defined at the project level
# required    : 0 a project can decide not to use this task field
#               1 all projects have to use this task field
# empty_ok    : 0 this field must always be assigned a value
#               1 empty value (null) is ok
# keep_history: 0 do not keep old field values in the project_history table
#               1 yes keep the old values in the history table
# special     : 0 process this field as usual
#               1 this field require some special processing
# custom      : 0 this is a CodeX field which semantic (label) cannot be customized
#               1 this field is a custom field which label can be user defined
#
CREATE TABLE project_field (
  project_field_id int(11) NOT NULL auto_increment,
  field_name varchar(255) NOT NULL default '',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  custom int(11) NOT NULL default '0',
  PRIMARY KEY  (project_field_id),
  KEY idx_project_field_name (field_name)
) TYPE=MyISAM;

#
# Table structure for table 'project_field_usage'
#
#
# project_field_id    : reference to the field id in project_field
# group_id        : group id this field usage belongs to (if 100 then
#                   this is either a system wide value
#                   or it is the default value for a project field if no
#                   project specific values are specified
# use_it          : 1 the project uses this field, 0 do not use it
# show_on_add     : 1 show this field on the task add form for non project
#                   members, 0 do not show it.
# show_on_add_members : 1 show this field on the task add form for project
#                   members with appropriate rigths, 0 do not show it.
# place           : A value indicating in which order the fields appear on
#                   the task submission screen (lowest first)
# custom_label    : custom field label as defined by the user. NULL if 
#                   it uses the system default label.
# custom_description : custom description as defined by the user.
#                   NULL if it uses the system default description
# custom_display_size : custom size as defined by the user. NULL if it 
#                   uses the system default display size.
# custom_empty_ok : 1 if empty value are allowed for this field. 0 if it
#                   is not. NULL if it uses the system default.
# custom_keep_history : 1 if field changes must be kept in the task history
#                   table. 0 otherwise. NULL if it uses the system default.
#
# Remark: for all fields declared in project_field table there must be a
# corresponding entry here (group_id = 100) to define default usage rules.
# For all other groups (real projects) only the fields actually used
# (or once used and then set back to unused) will be stored.
#
CREATE TABLE project_field_usage (
  project_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  show_on_add int(11) NOT NULL default '0',
  show_on_add_members int(11) NOT NULL default '0',
  place int(11) default NULL,
  custom_label varchar(255) default NULL,
  custom_description varchar(255) default NULL,
  custom_display_size varchar(255) default NULL,
  custom_empty_ok int(11) default NULL,
  custom_keep_history int(11) default NULL,
  KEY idx_project_fu_field_id (project_field_id),
  KEY idx_project_fu_group_id (group_id)
) TYPE=MyISAM;

#
# Table structure for table 'project_field_value'
#
#
# project_field_id: reference to the field id in project_field
# group_id        : group id this field value belongs to (if 100 then
#                   this is either a system wide value (see scope above)
#                   or it is the default value for a project field if no
#                   project specific values are specified
# value_id        : the id of the value
#                   0 is reserved for 'Any' and must *never* be stored here
#                   100 is reserved for 'None' and must be stored here
# value           : the text value
# description     : An explanation of the value (not used a lot but...)
# order_id        : number telling at which place in the select box
#                   a value must appear
# status          : A the value is active. It displays in select boxes
#                   H the value is hidden (not shown in select boxes but
#                   it's still here for old tasks using it
#                   P the value is permanent. It means that it is active and
#                   it cannot be changed to hidden by the project even if 
#                   task field has a 'project' scope (very useful to force
#                   some commonly accepted values to appear in the select
#                   box. The 'None' values are good examples of that)
#
#
CREATE TABLE project_field_value (
  project_fv_id int(11) NOT NULL auto_increment,
  project_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  value text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  PRIMARY KEY  (project_fv_id),
  KEY idx_project_fv_field_id (project_fv_id),
  KEY idx_project_fv_group_id (group_id),
  KEY idx_project_fv_value_id (value_id),
  KEY idx_project_fv_status (status)
) TYPE=MyISAM;

#
# Table structure for table 'project_type' for project type
#

CREATE TABLE project_type (
  project_type_id int(11) NOT NULL,
  label varchar(30) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  PRIMARY KEY  (project_type_id),
  KEY project_label_idx (label)
) TYPE=MyISAM;

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
  KEY branchid (branchid)
) TYPE=MyISAM;

CREATE TABLE cvs_commits (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  comm_when timestamp,
  whoid mediumint(9) DEFAULT '0' NOT NULL,
  KEY whoid (whoid),
  PRIMARY KEY (id)
) TYPE=MyISAM;

CREATE TABLE cvs_descs (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  description text,
  hash bigint(20) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id),
  KEY hash (hash)
) TYPE=MyISAM;

CREATE TABLE cvs_dirs (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  dir varchar(128) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE dir (dir)
);

CREATE TABLE cvs_files (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  file varchar(128) binary DEFAULT '' NOT NULL,
  PRIMARY KEY (id),
  UNIQUE file (file)
);

CREATE TABLE cvs_repositories (
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
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
  id mediumint(9) DEFAULT '0' NOT NULL auto_increment,
  branch varchar(64) binary DEFAULT '' NOT NULL, 
  PRIMARY KEY (id), 
  UNIQUE branch (branch)  
); 

CREATE TABLE cvs_tracks ( 
  group_artifact_id int(11),
  tracker varchar(64) binary DEFAULT '' NOT NULL, 
  artifact_id int(11) NOT NULL, 
  commit_id int(11) NOT NULL	
); 


#
# Generic trackers tables
#

#
# Table structure for table 'artifact_group_list'
#
CREATE TABLE artifact_group_list (
	group_artifact_id int(11) NOT NULL auto_increment,
	group_id int(11) NOT NULL,
	name text,
	description text,
	item_name text,
	is_public int(11) DEFAULT 0 NOT NULL,
	allow_anon int(11) DEFAULT 0 NOT NULL,
	email_all_updates int(11) DEFAULT 0 NOT NULL,
	email_address text NOT NULL,
	submit_instructions text,
	browse_instructions text,
	status char(1) DEFAULT 'A' NOT NULL,
	deletion_date int(11) NULL,
        instantiate_for_new_projects int(11) NOT NULL default '0',
	primary key (group_artifact_id),
	key idx_fk_group_id (group_id)
);

#
# Table structure for table 'artifact'
#
CREATE TABLE artifact (
	artifact_id int(11) NOT NULL auto_increment,
	group_artifact_id int(11) NOT NULL,
	status_id int(11) DEFAULT '1' NOT NULL,
	submitted_by int(11) DEFAULT '100' NOT NULL,
	open_date int(11) DEFAULT '0' NOT NULL,
	close_date int(11) DEFAULT '0' NOT NULL,
	summary text NOT NULL,
	details text NOT NULL,
	severity int(11) DEFAULT '0' NOT NULL,
	primary key (artifact_id),
	key idx_fk_group_artifact_id (group_artifact_id),
	key idx_fk_status_id (status_id)
);

#
# Table structure for table 'artifact_field_usage'
#
#
# project_field_id    : reference to the field id in project_field
# group_id        : group id this field usage belongs to (if 100 then
#                   this is either a system wide value
#                   or it is the default value for a project field if no
#                   project specific values are specified
# use_it          : 1 the project uses this field, 0 do not use it
# show_on_add     : 1 show this field on the task add form for non project
#                   members, 0 do not show it.
# show_on_add_members : 1 show this field on the task add form for project
#                   members with appropriate rigths, 0 do not show it.
# place           : A value indicating in which order the fields appear on
#                   the task submission screen (lowest first)
#
# Remark: for all fields declared in project_field table there must be a
# corresponding entry here (group_id = 100) to define default usage rules.
# For all other groups (real projects) only the fields actually used
# (or once used and then set back to unused) will be stored.
#
CREATE TABLE artifact_field_usage (
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  show_on_add int(11) NOT NULL default '0',
  show_on_add_members int(11) NOT NULL default '0',
  place int(11) default NULL,
  KEY idx_fk_field_id (field_id),
  KEY idx_fk_group_artifact_id (group_artifact_id)
);

#
# Table structure for table 'artifact_field'
#
# field_name  : the name of the field (must be indentical to the
#               column name in the artifact table
# data_type   : type of the value of this field
#               TEXT = 1 - INT = 2 - FLOAT = 3 - DATE = 4 - USER = 5
#
# display_type: TF= text field, TA=text area, SB=Select Box, NA=Not Applicable
# display_size: format X/Y
#               For TF X=visible field size, Y max length size
#               For TA X=number of columns, Y=number of rows
#               For SB Not applicable
# label       : short name (used on the HTML form)
# description : longer description of this field
# scope       : S if predefined values are for the entire Codex,
#               P if values can be re-defined at the project level
# required    : 0 a project can decide not to use this artifact field
#               1 all projects have to use this artifact field
# empty_ok    : 0 this field must always be assigned a value
#               1 empty value (null) is ok
# keep_history: 0 do not keep old field values in the artifact_history table
#               1 yes keep the old values in the history table
# special     : 0 process this field as usual
#               1 this field require some special processing
#
CREATE TABLE artifact_field (
  field_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL,
  field_name varchar(255) NOT NULL default '',
  data_type int(11) NOT NULL default '0',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  value_function varchar(255) default NULL,
  default_value text NOT NULL,
  PRIMARY KEY  (field_id,group_artifact_id),
  KEY idx_fk_field_name (field_name),
  KEY idx_fk_group_artifact_id (group_artifact_id)
);

#
# Table structure for table 'artifact_field_value'
#
CREATE TABLE artifact_field_value (
  field_id int(11) NOT NULL,
  artifact_id int(11) NOT NULL,
  valueInt int(11),
  valueText text,
  valueFloat float(10,4),
  valueDate int(11),
  KEY idx_field_id (field_id),
  KEY idx_artifact_id (artifact_id)
);

#
# Table structure for table 'artifact_report'
#
# Notes: 
# - scope='S' means a artifact report available to all projects
# (defined by CodeX Site administrators, group_id =100)
# - scope='P' means a artifact report available to all project members
# of project group_id (defined by project admin)
# - scope='I' means a personal (individual) artifact report only visible 
# and manageable by the owner. (defined by any project members)
#
CREATE TABLE artifact_report (
  report_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '100',
  user_id int(11) NOT NULL default '100',
  name varchar(80) default NULL,
  description varchar(255) default NULL,
  scope char(1) NOT NULL default 'I',
  PRIMARY KEY  (report_id),
  KEY group_artifact_id_idx (group_artifact_id),
  KEY user_id_idx (user_id),
  KEY scope_idx (scope)
);

#
# Table structure for table 'artifact_report_field'
#
# field_name      : name of the field used in this report (as defined in
#                   the 'field_name' column of artifact_field table
# show_on_query   : 1 show this field on the artifact query form as a selection
#                   criteria.
# show_on_result  : 1 show this field on the artifact query form as a column in
#                   the result list, 0 do not show it.
# place_query     : A value indicating in which order the fields appear on
#                   the artifact search criteria (lowest first)
# place_result    : A value indicating in which order the fields appear on
#                   the artifact search results table (lowest first)
# col_width       : A %age of the total window size that defines the width
#                   of the column in the report.
#
CREATE TABLE artifact_report_field (
  report_id int(11) NOT NULL default '100',
  field_name varchar(255) default NULL,
  show_on_query int(11) default NULL,
  show_on_result int(11) default NULL,
  place_query int(11) default NULL,
  place_result int(11) default NULL,
  col_width int(11) default NULL,
  KEY profile_id_idx (report_id),
  KEY field_name_idx (field_name)
);


#
# Table structure for table 'artifact_field_value_list'
#
#
# field_id    : reference to the field id in artifact_field
# group_artifact_id        : group id this field value belongs to (if 100 then
#                   this is either a system wide value (see scope above)
#                   or it is the default value for a project field if no
#                   project specific values are specified
# value_id        : the id of the value
#                   0 is reserved for 'Any' and must *never* be stored here
#                   100 is reserved for 'None' and must be stored here
# value           : the text value
# description     : An explanation of the value (not used a lot but...)
# order_id        : number telling at which place in the select box
#                   a value must appear
# status          : A the value is active. It displays in select boxes
#                   H the value is hidden (not shown in select boxes but
#                   it is still here for old artifacts using it
#                   P the value is permanent. It means that it is active and
#                   it cannot be changed to hidden by the project even if 
#                   artifact field has a 'project' scope (very useful to force
#                   some commonly accepted values to appear in the select
#                   box. The 'None' values are good examples of that)
#
#
CREATE TABLE artifact_field_value_list (
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  value text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  PRIMARY KEY  (field_id,group_artifact_id,value_id),
  KEY idx_fv_group_artifact_id (group_artifact_id),
  KEY idx_fv_value_id (value_id),
  KEY idx_fv_status (status)
);


#
# Table structure for table 'artifact_perm'
#
CREATE TABLE artifact_perm (
	id int(11) NOT NULL auto_increment,
	group_artifact_id int(11) NOT NULL,
	user_id int(11) NOT NULL,
	perm_level int(11) NOT NULL default '0',
	PRIMARY KEY  (id),
	UNIQUE KEY unique_user (group_artifact_id,user_id)
);

#
# Table structure for table 'artifact_history'
#

CREATE TABLE artifact_history (
  artifact_history_id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  new_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  email VARCHAR(100) NOT NULL,
  date int(11) default NULL,
  type int(11) default NULL,
  PRIMARY KEY  (artifact_history_id),
  KEY idx_artifact_history_artifact_id (artifact_id)
);

#
# Table structure for table 'artifact_canned_responses'
#

CREATE TABLE artifact_canned_responses (
  artifact_canned_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '0',
  title text,
  body text,
  PRIMARY KEY  (artifact_canned_id),
  KEY idx_artifact_canned_response_group_artifact_id (group_artifact_id)
);

#
# Table structure for table 'artifact_cc' for carbon-copied people
# on artifact email notification
#

CREATE TABLE artifact_cc (
  artifact_cc_id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  comment text NOT NULL,
  date int(11) NOT NULL default '0',
  PRIMARY KEY  (artifact_cc_id),
  KEY artifact_id_idx (artifact_id)
) TYPE=MyISAM;

#
# Table structure for table 'artifact_file'
#
CREATE TABLE artifact_file (
	id int(11) NOT NULL auto_increment,
	artifact_id int(11) NOT NULL default '0',
	description text NOT NULL,
	bin_data longblob NOT NULL,
	filename text NOT NULL,
	filesize integer NOT NULL,
	filetype text NOT NULL,
	adddate int(11) DEFAULT '0' NOT NULL,
	submitted_by int(11) NOT NULL,
	PRIMARY KEY  (id)
);

#
# Table structure for table 'artifact_notification'
# Says which user want to receive email notification depending on her role
# and artifact update events
#

CREATE TABLE artifact_notification (
  user_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  role_id int(11) NOT NULL default '0',
  event_id int(11) NOT NULL default '0',
  notify int(11) NOT NULL default '1',
  KEY user_id_idx (user_id),
  KEY group_artifact_id_idx (group_artifact_id)
);

#
# Table structure for table 'artifact_notification_event'
#  Rk: rank is an integer which allows to present the information
#     in a given order on the screen.
#

CREATE TABLE artifact_notification_event (
  event_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY event_id_idx (event_id),
  KEY group_artifact_id_idx (group_artifact_id)
);

#
# Table structure for table 'artifact_notification_event_default'
#
CREATE TABLE artifact_notification_event_default (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY event_id_idx (event_id)
);

#
# Table structure for table 'artifact_notification_role'
#

CREATE TABLE artifact_notification_role (
  role_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY role_id_idx (role_id),
  KEY group_artifact_id_idx (group_artifact_id)
);

#
# Table structure for table 'artifact_notification_role_default'
#
CREATE TABLE artifact_notification_role_default (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY role_id_idx (role_id)
);

#
# Table structure for table 'artifact_dependencies'
#

CREATE TABLE artifact_dependencies (
  artifact_depend_id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  is_dependent_on_artifact_id int(11) NOT NULL default '0',
  PRIMARY KEY  (artifact_depend_id),
  KEY idx_artifact_dependencies_artifact_id (artifact_id),
  KEY idx_actifact_is_dependent_on_artifact_id (is_dependent_on_artifact_id)
);

#
# Table structure for table 'artifact_watcher'
#
CREATE TABLE artifact_watcher (
  user_id int(11) NOT NULL default '0',
  watchee_id int(11) NOT NULL default '0',
  artifact_group_id int(11) NOT NULL default '0',
  INDEX `watchee_id_idx` (`watchee_id`,`artifact_group_id`),
  INDEX `user_id_idx` (`user_id`,`artifact_group_id`)  
);


#
# snippet category table
#
CREATE TABLE snippet_category (
  category_id int(11) NOT NULL,
  category_name varchar(255) NOT NULL default ''
);

#
# snippet type table
#
CREATE TABLE snippet_type (
  type_id int(11) NOT NULL,
  type_name varchar(255) NOT NULL default ''
);


#
# snippet license table
#
CREATE TABLE snippet_license (
  license_id int(11) NOT NULL,
  license_name varchar(255) NOT NULL default ''
);


#
# snippet language table
#
CREATE TABLE snippet_language (
  language_id int(11) NOT NULL,
  language_name varchar(255) NOT NULL default ''
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
        rank int(11) NOT NULL default '0',
	primary key (service_id),
        key idx_group_id(group_id)
);


#
# EOF
#
