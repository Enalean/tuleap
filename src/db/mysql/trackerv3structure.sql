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
	allow_copy int(11) DEFAULT 0 NOT NULL,
	submit_instructions text,
	browse_instructions text,
	status char(1) DEFAULT 'A' NOT NULL,
	deletion_date int(11) NULL,
        instantiate_for_new_projects int(11) NOT NULL default '0',
        stop_notification int(11) NOT NULL default '0',
	primary key (group_artifact_id),
	key idx_fk_group_id (group_id)
);

#
# Table structure for table 'artifact_field_set'
#
CREATE TABLE artifact_field_set (
    field_set_id int(11) unsigned NOT NULL auto_increment,
    group_artifact_id int(11) unsigned NOT NULL default '0',
    name text NOT NULL,
    description text NOT NULL,
    rank int(11) unsigned NOT NULL default '0',
    PRIMARY KEY  (field_set_id),
    KEY idx_fk_group_artifact_id (group_artifact_id)
);



#
# Table structure for table 'artifact'
#
CREATE TABLE artifact (
	artifact_id int(11) NOT NULL auto_increment,
	group_artifact_id int(11) NOT NULL,
    use_artifact_permissions tinyint(1) NOT NULL DEFAULT '0',
	status_id int(11) DEFAULT '1' NOT NULL,
	submitted_by int(11) DEFAULT '100' NOT NULL,
	open_date int(11) DEFAULT '0' NOT NULL,
	close_date int(11) DEFAULT '0' NOT NULL,
	last_update_date int(11) UNSIGNED DEFAULT '0' NOT NULL,
	summary text NOT NULL,
	details text NOT NULL,
	severity int(11) DEFAULT '0' NOT NULL,
	primary key (artifact_id),
	key idx_fk_group_artifact_id (group_artifact_id),
	key idx_fk_status_id (status_id),
	key idx_fk_submitted_by (submitted_by)
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
  place int(11) default NULL,
  INDEX idx_fk(field_id, group_artifact_id)
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
# scope       : S if predefined values are for the entire Codendi,
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
  field_set_id int(11) unsigned NOT NULL default '0',
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
  value_function TEXT,
  default_value text NOT NULL,
  PRIMARY KEY  (field_id,group_artifact_id),
  KEY idx_fk_field_name (field_name),
  KEY idx_fk_group_artifact_id (group_artifact_id),
  KEY idx_fname_grp (field_name(20), group_artifact_id)
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
  KEY idx_valueInt(artifact_id, field_id, valueInt),
  KEY xtrk_valueInt(valueInt)
);

#
# Table structure for table 'artifact_report'
#
# Notes:
# - scope='S' means a artifact report available to all projects
# (defined by site administrators, group_id =100)
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
  is_default int(1) default 0,
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
  field_name VARCHAR(255) NOT NULL,
  old_value text NOT NULL,
  new_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  email VARCHAR(100) NOT NULL,
  date int(11) default NULL,
  type int(11) default NULL,
  format tinyint NOT NULL default 0,
  PRIMARY KEY  (artifact_history_id),
  KEY idx_artifact_history_artifact_id (artifact_id),
  KEY field_name (field_name (10))
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
);

#
# Table structure for table 'artifact_file'
#
# Limit is 1 TB of data (default was 4GB)
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
	PRIMARY KEY  (id),
	KEY artifact_id (artifact_id)
) MAX_ROWS=1000000 AVG_ROW_LENGTH=1000000;

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
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY event_id_idx (event_id),
  KEY group_artifact_id_idx (group_artifact_id)
);

#
# Table structure for table 'artifact_notification_event_default'
#
CREATE TABLE artifact_notification_event_default (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY event_id_idx (event_id)
);

#
# Table structure for table 'artifact_notification_role'
#

CREATE TABLE artifact_notification_role (
  role_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY role_id_idx (role_id),
  KEY group_artifact_id_idx (group_artifact_id)
);

#
# Table structure for table 'artifact_notification_role_default'
#
CREATE TABLE artifact_notification_role_default (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
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
  INDEX watchee_id_idx (watchee_id,artifact_group_id),
  INDEX user_id_idx (user_id,artifact_group_id)
);

# DynamicFields tables
# {{{

DROP TABLE IF EXISTS artifact_rule;
CREATE TABLE artifact_rule (
  id int(11) unsigned NOT NULL auto_increment,
  group_artifact_id int(11) unsigned NOT NULL default '0',
  source_field_id int(11) unsigned NOT NULL default '0',
  source_value_id int(11) unsigned NOT NULL default '0',
  target_field_id int(11) unsigned NOT NULL default '0',
  rule_type tinyint(4) unsigned NOT NULL default '0',
  target_value_id int(11) unsigned default NULL,
  PRIMARY KEY  (id),
  KEY group_artifact_id (group_artifact_id)
);

# }}}

CREATE TABLE artifact_global_notification (
  id                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
  tracker_id        INT(11) NOT NULL ,
  addresses         TEXT NOT NULL ,
  all_updates       TINYINT(1) NOT NULL ,
  check_permissions TINYINT(1) NOT NULL ,
  INDEX (tracker_id)
);
