#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
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
#    modify the original sourceforge database to introduce the new tables
#    for the new bug tracking system. Also alter some existing tables
#    This new BTS is extensible in the sense that the site admin can add
#    as many new fields as desired in the system. Projects can decide whether
#    to use them and if so they can customize their values.
#
#----------------------------------------------------------------------
# Add new bug fields to the the bug table
# Make the default value to '100' (None) so that if the
# field is not used by a project it is however set properly
# when a new bug is created
#
# Important Remark: all new fields added in the bug table must have
# an entry in bug_field and bug_field_usage at least and possibly 
# bug_field_values if it's a field of type select box.
#
ALTER TABLE bug ADD category_version_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug ADD platform_version_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug ADD reproducibility_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug ADD size_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug ADD fix_release_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug ADD plan_release_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug ADD hours  float(10,2) DEFAULT '0.00' NOT NULL;
ALTER TABLE bug ADD component_version VARCHAR(255) DEFAULT '' NOT NULL;
ALTER TABLE bug ADD fix_release VARCHAR(255) DEFAULT '' NOT NULL;
ALTER TABLE bug ADD plan_release VARCHAR(255) DEFAULT '' NOT NULL;

# Make sure default value for historical fields is now '100'
# that is to say None (where "None" means something of course).
# Priority is a special case (default value is 5 - Major)
#
# (originally the default value was 0 but the bug tracking system
# was using a set of fixed and well known fields so the PHP script
# was in charge of setting the value to 100 at creation time)
#
ALTER TABLE bug MODIFY status_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug MODIFY priority int DEFAULT '5' NOT NULL;
ALTER TABLE bug MODIFY category_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug MODIFY bug_group_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug MODIFY resolution_id int DEFAULT '100' NOT NULL;
ALTER TABLE bug MODIFY submitted_by int DEFAULT '100' NOT NULL;
ALTER TABLE bug MODIFY assigned_to int DEFAULT '100' NOT NULL;

# 
# Priority is now severity and new priority field introduced
#
ALTER TABLE bug CHANGE priority severity INT (11) DEFAULT '5' not null;
ALTER TABLE bug ADD priority INT (11) DEFAULT '100' not null;

# user preferences for bug select/browse is now much longer
# so change it from varchar(20) to TEXT
ALTER TABLE user_preferences MODIFY preference_value TEXT;

# add a column in the bug_history field to store the comment type
# (the type column will be NULL when the history field is not a comment)
ALTER TABLE bug_history ADD type int;

#
# Comments about fields
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
# scope       : P if predefined values are for the entire Codex,
#               S if values can be re-defined at the project level
# required    : 0 a project can decide not to use this bug field
#               1 all projects have to use this bug field
# empty_ok    : 0 this field must always be assigned a value
#               1 empty value (null) is ok
# keep_history: 0 do not keep old field values in the bug_history table
#               1 yes keep the old values in the history table
# special     : 0 process this field as usual
#               1 this field require some special processing
#
CREATE TABLE bug_field (
  bug_field_id int(11)  NOT NULL auto_increment,
  field_name varchar(255) NOT NULL,
  display_type varchar(255) NOT NULL,
  display_size varchar(255) NOT NULL,
  label varchar(255) DEFAULT '' NOT NULL,
  description text DEFAULT '' NOT NULL,
  scope char(1) NOT NULL,
  required int(11) DEFAULT '0' NOT NULL,
  empty_ok int(11) DEFAULT '0' NOT NULL,
  keep_history int(11) DEFAULT '0' NOT NULL,
  special int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (bug_field_id),
  KEY idx_bug_field_name (field_name)
);

#
# Comments about fields
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
CREATE TABLE bug_field_value (
  bug_fv_id int(11) NOT NULL auto_increment,
  bug_field_id int(11) NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  value_id int(11) NOT NULL,
  value text DEFAULT '' NOT NULL,
  description text DEFAULT '' NOT NULL,
  order_id int(11) DEFAULT 0 NOT NULL,
  status char(1) DEFAULT 'A' NOT NULL,
  PRIMARY KEY (bug_fv_id),
  KEY idx_bug_fv_field_id (bug_fv_id),
  KEY idx_bug_fv_group_id (group_id),
  KEY idx_bug_fv_value_id (value_id),
  KEY idx_bug_fv_status (status)
);


#
# Comments about fields
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
#
# Remark: for all fields declared in bug_field table there must be a
# corresponding entry here (group_id = 100) to define default usage rules.
# For all other groups (real projects) only the fields actually used
# (or once used and then set back to unused) will be stored.
#
CREATE TABLE bug_field_usage (
  bug_field_id int(11)  NOT NULL,
  group_id int(11) DEFAULT '0' NOT NULL,
  use_it int(11) DEFAULT '0' NOT NULL,
  show_on_add int(11) DEFAULT '0' NOT NULL,
  show_on_add_members int(11) DEFAULT '0' NOT NULL,
  place int(11) DEFAULT '0' NOT NULL,
  KEY idx_bug_fu_field_id (bug_field_id),
  KEY idx_bug_fu_group_id (group_id)
);

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
  group_id int(11) DEFAULT '100' NOT NULL,
  user_id int(11) DEFAULT '100' NOT NULL,
  name VARCHAR(80),
  description VARCHAR(255),
  scope VARCHAR(1) DEFAULT 'I' NOT NULL,
  PRIMARY KEY (report_id),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY scope_idx (scope)
);


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
  report_id int(11) DEFAULT '100' NOT NULL,
  field_name VARCHAR(255),
  show_on_query int(11),
  show_on_result int(11),
  place_query int(11),
  place_result int(11),
  col_width int(11),
  KEY report_id_idx (report_id)
);

#
# Table structure for table 'bug_file' for bug attachments
#

CREATE TABLE bug_file (
  bug_file_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL,
  submitted_by int(11) DEFAULT '0' NOT NULL,
  date int(11) DEFAULT '0' NOT NULL,
  description text DEFAULT '' NOT NULL,	
  file longblob DEFAULT '' NOT NULL,
  filename text DEFAULT '' NOT NULL,
  filesize int(11) DEFAULT '0' NOT NULL,
  filetype text DEFAULT '' NOT NULL,
  PRIMARY KEY (bug_file_id),
  KEY bug_id_idx (bug_id)
);

#
# EOF
#
