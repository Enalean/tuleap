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
# Add the Global Notification Address for the task manager
# Add also the project_cc table (allow to users to be in cc)
#
# References:
# Task #2250
#
# Dependencies:
# None
#
# 
alter table groups ADD new_task_address text;
alter table groups ADD send_all_tasks int(11) NOT NULL default '0';

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

# ==============================
# project_notification_role table
# ==============================
# Create the list of roles a user can play wrt to a task
#
INSERT INTO project_notification_role VALUES (1,'SUBMITTER','Submitter', 'The person who submitted the task',10);
INSERT INTO project_notification_role VALUES (2,'ASSIGNEE','Assignee','The person to whom the task was assigned',20);
INSERT INTO project_notification_role VALUES (3,'CC','CC','The person who is in the CC list',30);
INSERT INTO project_notification_role VALUES (4,'COMMENTER','Commenter','A person who once posted a follow-up comment',40);

# ==============================
# project_notification_event table
# ==============================
# Create the list of events that can occur in a project update
#
INSERT INTO project_notification_event VALUES (1,'ROLE_CHANGE','Role has changed','I\'m added to or removed from this role',10);
INSERT INTO project_notification_event VALUES (2,'NEW_COMMENT','New comment','A new followup comment is added',20);
INSERT INTO project_notification_event VALUES (3,'NEW_FILE','New attachment','A new file attachment is added',30);
INSERT INTO project_notification_event VALUES (4,'CC_CHANGE','CC Change','A new CC address is added/removed',40);
INSERT INTO project_notification_event VALUES (5,'CLOSED','Task closed','The task is closed',50);
INSERT INTO project_notification_event VALUES (6,'PSS_CHANGE','PSS change','Priority,Status changes',60);
INSERT INTO project_notification_event VALUES (7,'ANY_OTHER_CHANGE','Any other Changes','Any changes not mentioned above',70);
INSERT INTO project_notification_event VALUES (8,'I_MADE_IT','I did it','I am the author of the change',80);
INSERT INTO project_notification_event VALUES (9,'NEW_TASK','New Task','A new task has been submitted',90);

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
#  Default values for the Task Tracking System
#

# ==============================
# Project field table
# ==============================
#
INSERT INTO project_field \
  VALUES (90,'project_task_id','TF','6/10','Project Task ID','Unique project task identifier','S',1,0,0,1,0);
INSERT INTO project_field \
  VALUES (91,'group_id','TF','','Group ID','Unique project identifier','S',1,0,0,1,0);
INSERT INTO project_field \
  VALUES (92,'created_by','SB','','Created by','User who originally created the task','S',1,0,0,1,0);
INSERT INTO project_field \
  VALUES (94,'start_date','DF','10/15','Start date','Date and time when the task starts','S',1,1,1,0,0);
INSERT INTO project_field \
  VALUES (95,'end_date','DF','10/15','End date','Date and time when the task is finish','S',1,1,1,0,0);
INSERT INTO project_field \
  VALUES (96,'group_project_id','SB','','Subproject','Subproject','P',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (97,'percent_complete','SB','','Percent complete','The percent completion','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (98,'hours','TF','5/10','Effort','The estimation to do the task','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (99,'priority','SB','','Priority','Level of priority for this task','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (100,'status_id','SB','','Status','Task status','S',1,0,1,0,0);
INSERT INTO project_field \
  VALUES (102,'summary','TF','60/120','Summary','One line description of the task','S',1,0,1,1,0);
INSERT INTO project_field \
  VALUES (103,'details','TA','60/7','Original Submission','A full description of the task','S',1,1,1,1,0);

# ==============================
# Project field value table
# ==============================
#

#
# Status (percent_complete = 97)
#
INSERT INTO project_field_value VALUES (101,97,100,1000,'Not started','The task is not started',1,'P');
INSERT INTO project_field_value VALUES (102,97,100,1005,'5%','',5,'P');
INSERT INTO project_field_value VALUES (103,97,100,1015,'15%','',15,'P');
INSERT INTO project_field_value VALUES (104,97,100,1020,'20%','',20,'P');
INSERT INTO project_field_value VALUES (105,97,100,1025,'25%','',25,'P');
INSERT INTO project_field_value VALUES (106,97,100,1030,'30%','',30,'P');
INSERT INTO project_field_value VALUES (107,97,100,1035,'35%','',35,'P');
INSERT INTO project_field_value VALUES (108,97,100,1040,'40%','',40,'P');
INSERT INTO project_field_value VALUES (109,97,100,1045,'45%','',45,'P');
INSERT INTO project_field_value VALUES (110,97,100,1050,'50%','',50,'P');
INSERT INTO project_field_value VALUES (111,97,100,1055,'55%','',55,'P');
INSERT INTO project_field_value VALUES (112,97,100,1060,'60%','',60,'P');
INSERT INTO project_field_value VALUES (113,97,100,1065,'65%','',65,'P');
INSERT INTO project_field_value VALUES (114,97,100,1070,'70%','',70,'P');
INSERT INTO project_field_value VALUES (115,97,100,1075,'75%','',75,'P');
INSERT INTO project_field_value VALUES (116,97,100,1080,'80%','',80,'P');
INSERT INTO project_field_value VALUES (117,97,100,1085,'85%','',85,'P');
INSERT INTO project_field_value VALUES (118,97,100,1090,'90%','',90,'P');
INSERT INTO project_field_value VALUES (119,97,100,1095,'95%','',95,'P');
INSERT INTO project_field_value VALUES (120,97,100,1100,'100%','',100,'P');

#
# Priority (priority = 99)
#
INSERT INTO project_field_value VALUES (121,99,100,1,'1 - Lowest','',1,'P');
INSERT INTO project_field_value VALUES (122,99,100,2,'2','',2,'P');
INSERT INTO project_field_value VALUES (123,99,100,3,'3','',3,'P');
INSERT INTO project_field_value VALUES (124,99,100,4,'4','',4,'P');
INSERT INTO project_field_value VALUES (125,99,100,5,'5 - Medium','',5,'P');
INSERT INTO project_field_value VALUES (126,99,100,6,'6','',6,'P');
INSERT INTO project_field_value VALUES (127,99,100,7,'7','',7,'P');
INSERT INTO project_field_value VALUES (128,99,100,8,'8','',8,'P');
INSERT INTO project_field_value VALUES (129,99,100,9,'9 - Highest','',9,'P');

#
# Status (status_id = 100)
#
INSERT INTO project_field_value VALUES (130,100,100,100,'None','',1,'P');
INSERT INTO project_field_value VALUES (131,100,100,1,'Open','',2,'P');
INSERT INTO project_field_value VALUES (132,100,100,2,'Closed','',3,'P');
INSERT INTO project_field_value VALUES (133,100,100,3,'Deleted','',4,'P');
INSERT INTO project_field_value VALUES (134,100,100,4,'Suspended','',5,'P');

# ==============================
# Project field usage table
# ==============================
# Insert field usage information for group 100 (None). This will be
# the default pattern for all projects as long as they do not define
# their own settings

# Project task ID (new_project_task_id = 90)
#
INSERT INTO project_field_usage VALUES (90,100,1,1,1,10,NULL,NULL,NULL,NULL,NULL);

# Group Id (group_id = 91)
#
INSERT INTO project_field_usage VALUES (91,100,1,1,1,20,NULL,NULL,NULL,NULL,NULL);

# Created by (created_by = 92)
#
INSERT INTO project_field_usage VALUES (92,100,1,1,1,30,NULL,NULL,NULL,NULL,NULL);

# Start date (start_date = 94)
#
INSERT INTO project_field_usage VALUES (94,100,1,1,1,40,NULL,NULL,NULL,NULL,NULL);

# Start date (end_date = 95)
#
INSERT INTO project_field_usage VALUES (95,100,1,1,1,50,NULL,NULL,NULL,NULL,NULL);

# Subproject (group_project_id = 96)
#
INSERT INTO project_field_usage VALUES (96,100,1,1,1,60,NULL,NULL,NULL,NULL,NULL);

# Percent complete (percent_complete = 97)
#
INSERT INTO project_field_usage VALUES (97,100,1,1,1,70,NULL,NULL,NULL,NULL,NULL);

# Effort (effort = 98)
#
INSERT INTO project_field_usage VALUES (98,100,1,1,1,80,NULL,NULL,NULL,NULL,NULL);

# Priority (priority = 99)
#
INSERT INTO project_field_usage VALUES (99,100,1,1,1,80,NULL,NULL,NULL,NULL,NULL);

# Status (status = 100)
#
INSERT INTO project_field_usage VALUES (100,100,1,1,1,90,NULL,NULL,NULL,NULL,NULL);

# Summary (summary = 102)
#
INSERT INTO project_field_usage VALUES (102,100,1,1,1,110,NULL,NULL,NULL,NULL,NULL);

# Original Submission (details = 103)
#
INSERT INTO project_field_usage VALUES (103,100,1,1,1,120,NULL,NULL,NULL,NULL,NULL);

#
# Migration
#
update project_task set percent_complete = percent_complete + 1000;
update project_history set old_value = old_value + 1000 where field_name = 'percent_complete';
