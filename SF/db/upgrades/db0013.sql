# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001-2002. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
# Database upgrade script (see dbXXXX_template for instructions)
#
# This SQL script allows you to upgrade the CodeX database. In most cases
# this sql script relates to a well indentified modification in the 
# CodeX source code. All references are included
# below.
#
# Description
# Generic trackers
#
#
# References:
# See Task #2627
#
# Dependencies:
# None
#
# 
# SQL script comes next...
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
#                   it's still here for old artifacts using it
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
# Modification for table 'groups'
#
alter table groups ADD use_trackers int(11) NOT NULL default '1';
alter table groups ADD activate_old_bug int(11) NOT NULL default '0';
alter table groups ADD activate_old_task int(11) NOT NULL default '0';
alter table groups ADD activate_old_sr int(11) NOT NULL default '0';

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


#****************************************************************
#*******               V  A  L  U  E  S              ************
#****************************************************************

--
-- Dumping data for table 'artifact_group_list'
--

INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (1, 100, 'Bugs', 'Bugs Tracker', 'bug', 1, 0, 0, '', NULL, NULL);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (2, 100, 'Tasks', 'Tasks Manager', 'task', 1, 0, 0, '', NULL, NULL);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (3, 100, 'Supports', 'Support Requests', 'SR', 1, 1, 0, '', NULL, NULL);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (4, 100, 'Empty', 'Empty tracker', '', 0, 0, 0, '', NULL, NULL);

--
-- This tracker has the id 100 to force the next id to be greater than 100
-- 100 is a special value (None value)
--
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions) VALUES (100, 100, 'None', 'None', '', 0, 0, 0, '', NULL, NULL);

--
-- Dumping data for table 'artifact_field'
--


INSERT INTO artifact_field VALUES (7,1,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (6,1,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (5,1,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (1,1,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (4,1,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (3,1,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (2,1,'status_id',2,'SB','2','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (8,1,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (10,1,'comment_type_id',2,'SB','','Comment Type','Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)','',0,1,0,1,NULL,'');
INSERT INTO artifact_field VALUES (9,1,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (16,1,'resolution_id',2,'SB','','Resolution','How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (20,1,'bug_group_id',2,'SB','','Group','Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (2,2,'percent_complete',2,'SB','2','Percent complete','Percentage of completion','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (3,2,'priority',2,'SB','','Priority','How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)','',0,0,1,0,NULL,'180');
INSERT INTO artifact_field VALUES (4,2,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','',0,1,1,0,NULL,'0.00');
INSERT INTO artifact_field VALUES (5,2,'start_date',4,'DF','','Start Date','Start Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (6,2,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (7,2,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (8,2,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (11,2,'status_id',2,'SB','2','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (1,2,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (10,2,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (9,2,'multi_assigned_to',5,'MB','','Assigned to (multiple)','Who is in charge of this artifact','',0,1,1,0,'artifact_technicians','');
INSERT INTO artifact_field VALUES (12,2,'subproject_id',2,'SB','1','Subproject','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (9,3,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (8,3,'priority',2,'SB','','Priority','How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)','',0,0,1,0,NULL,'180');
INSERT INTO artifact_field VALUES (7,3,'status_id',2,'SB','2','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (6,3,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (5,3,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (4,3,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,3,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (2,3,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (1,3,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');

INSERT INTO artifact_field VALUES (11,1,'category_version_id',2,'SB','','Component Version','The version of the System Component (aka Category) impacted by the artifact','P',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (12,1,'platform_version_id',2,'SB','','Platform Version','The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)','P',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (13,1,'reproducibility_id',2,'SB','','Reproducibility','How easy is it to reproduce the artifact','S',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (14,1,'size_id',2,'SB','','Size (loc)','The size of the code you need to develop or rework in order to fix the artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (15,1,'fix_release_id',2,'SB','','Fixed Release','The release in which the artifact was actually fixed','P',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (17,1,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (18,1,'plan_release_id',2,'SB','','Planned Release','The release in which you initially planned the artifact to be fixed','P',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (19,1,'component_version',1,'TF','10/40','Component Version','Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (22,1,'priority',2,'SB','','Priority','How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (23,1,'keywords',1,'TF','60/120','Keywords','A list of comma separated keywords associated with a artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (24,1,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (26,1,'originator_name',1,'TF','20/40','Originator Name','The name of the person who reported the artifact (if different from the submitter field)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (27,1,'originator_email',1,'TF','20/40','Originator Email','Email address of the person who reported the artifact. Automatically included in the artifact email notification process.','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (28,1,'originator_phone',1,'TF','10/40','Originator Phone','Phone number of the person who reported the artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (29,1,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');

INSERT INTO artifact_field VALUES (13,2,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (14,2,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');

INSERT INTO artifact_field VALUES (10,3,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (11,3,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');

INSERT INTO artifact_field VALUES (1,4,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'group_members','');
INSERT INTO artifact_field VALUES (2,4,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,4,'close_date',4,'DF','','End Date','End Date','',0,0,0,0,NULL,'');
INSERT INTO artifact_field VALUES (4,4,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (5,4,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (6,4,'status_id',2,'SB','2','Status','Artifact Status','',0,0,1,0,NULL,'20');
INSERT INTO artifact_field VALUES (7,4,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (8,4,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');

--
-- Dumping data for table 'artifact_field_usage'
--


INSERT INTO artifact_field_usage VALUES (7,1,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (6,1,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (5,1,1,1,1,900);
INSERT INTO artifact_field_usage VALUES (1,1,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (4,1,1,1,1,50);
INSERT INTO artifact_field_usage VALUES (3,1,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (2,1,1,0,0,60);
INSERT INTO artifact_field_usage VALUES (8,1,1,1,1,20);
INSERT INTO artifact_field_usage VALUES (10,1,1,0,0,NULL);
INSERT INTO artifact_field_usage VALUES (9,1,1,1,1,1000);
INSERT INTO artifact_field_usage VALUES (16,1,1,0,0,40);
INSERT INTO artifact_field_usage VALUES (20,1,1,1,1,30);
INSERT INTO artifact_field_usage VALUES (2,2,1,1,1,20);
INSERT INTO artifact_field_usage VALUES (3,2,1,1,1,30);
INSERT INTO artifact_field_usage VALUES (4,2,1,1,1,40);
INSERT INTO artifact_field_usage VALUES (5,2,1,1,1,60);
INSERT INTO artifact_field_usage VALUES (6,2,1,1,1,80);
INSERT INTO artifact_field_usage VALUES (7,2,1,1,1,900);
INSERT INTO artifact_field_usage VALUES (8,2,1,1,1,1000);
INSERT INTO artifact_field_usage VALUES (11,2,1,0,0,50);
INSERT INTO artifact_field_usage VALUES (1,2,1,1,1,1);
INSERT INTO artifact_field_usage VALUES (10,2,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (9,3,1,1,1,NULL);
INSERT INTO artifact_field_usage VALUES (8,3,1,0,0,40);
INSERT INTO artifact_field_usage VALUES (7,3,1,0,0,30);
INSERT INTO artifact_field_usage VALUES (6,3,1,0,0,20);
INSERT INTO artifact_field_usage VALUES (5,3,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (4,3,1,1,1,5);
INSERT INTO artifact_field_usage VALUES (3,3,1,1,1,1000);
INSERT INTO artifact_field_usage VALUES (2,3,1,1,1,900);
INSERT INTO artifact_field_usage VALUES (1,3,1,1,1,1);
INSERT INTO artifact_field_usage VALUES (9,2,1,1,1,70);
INSERT INTO artifact_field_usage VALUES (12,2,1,1,1,10);

INSERT INTO artifact_field_usage VALUES (11,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (12,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (13,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (14,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (15,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (17,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (18,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (19,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (22,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (23,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (24,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (26,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (27,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (28,1,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (29,1,0,0,0,0);

INSERT INTO artifact_field_usage VALUES (13,2,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (14,2,0,0,0,0);

INSERT INTO artifact_field_usage VALUES (10,3,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (11,3,0,0,0,0);

INSERT INTO artifact_field_usage VALUES (1,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (2,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (3,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (4,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (5,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (6,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (7,4,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (8,4,0,0,0,0);

--
-- Dumping data for table 'artifact_field_value_list'
--


INSERT INTO artifact_field_value_list VALUES (2,1,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (2,1,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (2,1,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (2,1,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (3,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,9,'9 - Critical','',90,'P');
INSERT INTO artifact_field_value_list VALUES (10,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (16,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (16,1,1,'Fixed','The bug was resolved',20,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,2,'Invalid','The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)',30,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,3,'Wont Fix','The bug won''t be fixed (probably because it is very minor)',40,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,4,'Later','The bug will be fixed later (no date given)',50,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,5,'Remind','The bug will be fixed later but keep in the remind state for easy identification',60,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,6,'Works for me','The project team was unable to reproduce the bug',70,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,7,'Duplicate','This bug is already covered by another bug description (see related bugs list)',80,'A');
INSERT INTO artifact_field_value_list VALUES (11,2,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (11,2,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (11,2,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (11,2,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (12,2,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (3,2,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (3,2,120,'Later','',20,'V');
INSERT INTO artifact_field_value_list VALUES (3,2,130,'Later+','',30,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,140,'Later++','',40,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,150,'Low','',50,'V');
INSERT INTO artifact_field_value_list VALUES (3,2,160,'Low+','',60,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,170,'Low++','',70,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,180,'Normal','',80,'V');
INSERT INTO artifact_field_value_list VALUES (3,2,190,'Normal+','',90,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,200,'Normal++','',100,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,210,'High','',110,'V');
INSERT INTO artifact_field_value_list VALUES (3,2,220,'High+','',120,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,230,'High++','',130,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,240,'Immediate','',140,'V');
INSERT INTO artifact_field_value_list VALUES (3,2,250,'Immediate+','',150,'H');
INSERT INTO artifact_field_value_list VALUES (3,2,260,'Immediate++','',160,'H');
INSERT INTO artifact_field_value_list VALUES (2,2,1095,'95%','',95,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1090,'90%','',90,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1085,'85%','',85,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1080,'80%','',80,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1075,'75%','',75,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1070,'70%','',70,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1065,'65%','',65,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1060,'60%','',60,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1055,'55%','',55,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1050,'50%','',50,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1045,'45%','',45,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1040,'40%','',40,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1035,'35%','',35,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1030,'30%','',30,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1025,'25%','',25,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1020,'20%','',20,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1015,'15%','',15,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1010,'10%','',10,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1000,'Not started','',0,'V');
INSERT INTO artifact_field_value_list VALUES (2,2,1100,'100%','',100,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (7,3,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (5,3,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,3,1095,'95%','',95,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1090,'90%','',90,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1085,'85%','',85,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1080,'80%','',80,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1075,'75%','',75,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1070,'70%','',70,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1065,'65%','',65,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1060,'60%','',60,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1055,'55%','',55,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1050,'50%','',50,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1045,'45%','',45,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1040,'40%','',40,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1035,'35%','',35,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1030,'30%','',30,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1025,'25%','',25,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1020,'20%','',20,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1015,'15%','',15,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1010,'10%','',10,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1000,'Not started','',0,'V');
INSERT INTO artifact_field_value_list VALUES (8,3,1100,'100%','',100,'V');

INSERT INTO artifact_field_value_list VALUES (11,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (12,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (13,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (14,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (15,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (18,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (22,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (24,1,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (14,2,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,9,'9 - Critical','',90,'P');

INSERT INTO artifact_field_value_list VALUES (11,3,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,9,'9 - Critical','',90,'P');

INSERT INTO artifact_field_value_list VALUES (6,4,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (6,4,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (6,4,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (6,4,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');
INSERT INTO artifact_field_value_list VALUES (7,4,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,9,'9 - Critical','',90,'P');

--
-- Dumping data for table 'artifact_report'
--


INSERT INTO artifact_report VALUES (100,100,100,'Default','The system default artifact report','S');
INSERT INTO artifact_report VALUES (2,2,100,'Tasks','Tasks Report','P');
INSERT INTO artifact_report VALUES (3,3,100,'SR','Support Requests Report','P');
INSERT INTO artifact_report VALUES (4,1,100,'Bugs','Bugs Reports','P');


--
-- Dumping data for table 'artifact_report_field'
--

INSERT INTO artifact_report_field VALUES (100,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (100,'assigned_to',1,1,30,40,NULL);
INSERT INTO artifact_report_field VALUES (100,'status_id',1,0,20,NULL,NULL);
INSERT INTO artifact_report_field VALUES (100,'artifact_id',1,1,50,10,NULL);
INSERT INTO artifact_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (100,'open_date',1,1,40,30,NULL);
INSERT INTO artifact_report_field VALUES (100,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (100,'severity',0,0,NULL,NULL,NULL);
INSERT INTO artifact_report_field VALUES (2,'subproject_id',1,1,10,30,NULL);
INSERT INTO artifact_report_field VALUES (2,'multi_assigned_to',1,1,20,60,NULL);
INSERT INTO artifact_report_field VALUES (2,'status_id',1,1,30,100,NULL);
INSERT INTO artifact_report_field VALUES (2,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (2,'start_date',0,1,NULL,40,NULL);
INSERT INTO artifact_report_field VALUES (2,'close_date',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (2,'hours',0,1,NULL,70,NULL);
INSERT INTO artifact_report_field VALUES (2,'percent_complete',0,1,NULL,80,NULL);
INSERT INTO artifact_report_field VALUES (2,'artifact_id',0,1,NULL,1,NULL);
INSERT INTO artifact_report_field VALUES (3,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'status_id',1,0,30,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (3,'open_date',0,1,NULL,30,NULL);
INSERT INTO artifact_report_field VALUES (3,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (3,'severity',0,0,NULL,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (3,'assigned_to',1,1,20,40,NULL);
INSERT INTO artifact_report_field VALUES (4,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (4,'assigned_to',1,1,30,40,NULL);
INSERT INTO artifact_report_field VALUES (4,'status_id',1,0,40,NULL,NULL);
INSERT INTO artifact_report_field VALUES (4,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (4,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (4,'open_date',0,1,NULL,30,NULL);
INSERT INTO artifact_report_field VALUES (4,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (4,'bug_group_id',1,0,20,NULL,NULL);


# ==============================
# artifact_notification_role table
# ==============================
# Create the list of roles a user can play wrt to a artifact
#
INSERT INTO artifact_notification_role_default VALUES (1,'SUBMITTER','Submitter', 'The person who submitted the artifact',10);
INSERT INTO artifact_notification_role_default VALUES (2,'ASSIGNEE','Assignee','The person to whom the artifact was assigned',20);
INSERT INTO artifact_notification_role_default VALUES (3,'CC','CC','The person who is in the CC list',30);
INSERT INTO artifact_notification_role_default VALUES (4,'COMMENTER','Commenter','A person who once posted a follow-up comment',40);

# ==============================
# artifact_notification_event table
# ==============================
# Create the list of events that can occur in a artifact update
#
INSERT INTO artifact_notification_event_default VALUES (1,'ROLE_CHANGE','Role has changed','I\'m added to or removed from this role',10);
INSERT INTO artifact_notification_event_default VALUES (2,'NEW_COMMENT','New comment','A new followup comment is added',20);
INSERT INTO artifact_notification_event_default VALUES (3,'NEW_FILE','New attachment','A new file attachment is added',30);
INSERT INTO artifact_notification_event_default VALUES (4,'CC_CHANGE','CC Change','A new CC address is added/removed',40);
INSERT INTO artifact_notification_event_default VALUES (5,'CLOSED','artifact closed','The artifact is closed',50);
INSERT INTO artifact_notification_event_default VALUES (6,'PSS_CHANGE','PSS change','Priority,Status,Severity changes',60);
INSERT INTO artifact_notification_event_default VALUES (7,'ANY_OTHER_CHANGE','Any other Changes','Any changes not mentioned above',70);
INSERT INTO artifact_notification_event_default VALUES (8,'I_MADE_IT','I did it','I am the author of the change',80);
INSERT INTO artifact_notification_event_default VALUES (9,'NEW_ARTIFACT','New artifact','A new artifact has been submitted',90);

