--  
--  Table structure for workflow_tracker
--  
DROP TABLE IF EXISTS tracker_workflow;
CREATE TABLE IF NOT EXISTS tracker_workflow (
  workflow_id int(11) NOT NULL auto_increment  PRIMARY KEY,
  tracker_id int(11) NOT NULL,
  field_id int(11) NOT NULL,
  is_used tinyint(1) NOT NULL,
  INDEX idx_wf_tracker_id( tracker_id ),
  INDEX idx_wf_field_id( field_id )  
) ENGINE=InnoDB;

--  
--  Table structure for workflow_transition
-- 
DROP TABLE IF EXISTS tracker_workflow_transition;
CREATE TABLE IF NOT EXISTS tracker_workflow_transition (
  transition_id int(11) NOT NULL auto_increment  PRIMARY KEY,
  from_id int(11) default NULL,
  to_id int(11) NOT NULL,
  workflow_id int(11) NOT NULL,
  INDEX idx_wf_workflow_id( workflow_id )
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_workflow_transition_condition_field_notempty;
CREATE TABLE  tracker_workflow_transition_condition_field_notempty(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    transition_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL
) ENGINE=InnoDB;

--  
--  Table structure for workflow_transition_postactions_field_date
-- 
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_date;
CREATE TABLE IF NOT EXISTS tracker_workflow_transition_postactions_field_date (
  id int(11) UNSIGNED NOT NULL auto_increment  PRIMARY KEY,
  transition_id int(11) NOT NULL,
  field_id int(11) UNSIGNED default NULL,
  value_type tinyint(2) default NULL,
  INDEX idx_wf_transition_id( transition_id )
) ENGINE=InnoDB;

--  
--  Table structure for workflow_transition_postactions_field_int
-- 
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_int;
CREATE TABLE IF NOT EXISTS tracker_workflow_transition_postactions_field_int (
  id int(11) UNSIGNED NOT NULL auto_increment  PRIMARY KEY,
  transition_id int(11) NOT NULL,
  field_id int(11) UNSIGNED default NULL,
  value int(11) default NULL,
  INDEX idx_wf_transition_id( transition_id )
) ENGINE=InnoDB;

--  
--  Table structure for workflow_transition_postactions_field_float
-- 
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_float;
CREATE TABLE IF NOT EXISTS tracker_workflow_transition_postactions_field_float (
  id int(11) UNSIGNED NOT NULL auto_increment  PRIMARY KEY,
  transition_id int(11) NOT NULL,
  field_id int(11) UNSIGNED default NULL,
  value FLOAT(10,4) default NULL,
  INDEX idx_wf_transition_id( transition_id )
) ENGINE=InnoDB;

--  
--  Table structure for tracker_workflow_transition_postactions_cibuild
-- 
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_cibuild;
CREATE TABLE IF NOT EXISTS tracker_workflow_transition_postactions_cibuild (
  id int(11) UNSIGNED NOT NULL auto_increment  PRIMARY KEY,
  transition_id int(11) NOT NULL,
  job_url varchar(255) default NULL,
  INDEX idx_wf_transition_id( transition_id )
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_widget_renderer;
CREATE TABLE tracker_widget_renderer (
   id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
   owner_id int(11) unsigned NOT NULL default '0',
   owner_type varchar(1) NOT NULL default 'u',
   title varchar(255) NOT NULL,
   renderer_id INT(11) NOT NULL,
   KEY (owner_id, owner_type)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker;
CREATE TABLE tracker(
    id int( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    group_id INT( 11 ) NOT NULL ,
    name TEXT,
    description TEXT,
    item_name TEXT,
    allow_copy INT( 11 ) NOT NULL default '0',
    submit_instructions TEXT,
    browse_instructions TEXT,
    status CHAR( 1 ) NOT NULL default 'A',
    deletion_date INT( 11 ) default NULL ,
    instantiate_for_new_projects INT( 11 ) NOT NULL default '0',
    stop_notification INT( 11 ) NOT NULL default '0',
    from_tv3_id INT(11) NULL,
    INDEX idx_fk_group_id( group_id )
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field;
CREATE TABLE tracker_field(
    id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    old_id INT(11) UNSIGNED NULL,
    tracker_id INT(11) UNSIGNED NOT NULL,
    parent_id INT( 11 ) UNSIGNED NOT NULL default '0',
    formElement_type VARCHAR(255) NOT NULL,
    name TEXT NOT NULL ,
    label TEXT NOT NULL ,
    description TEXT NOT NULL ,
    use_it TINYINT(1) NOT NULL ,
    rank INT( 11 ) UNSIGNED NOT NULL,
    scope CHAR( 1 ) NOT NULL,
    required TINYINT(1) NULL,
    notifications TINYINT(1) NULL,
    original_field_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
    INDEX idx_fk_old_id( old_id ),
    INDEX idx_fk_tracker_id( tracker_id ),
    INDEX idx_fk_parent_id( parent_id )
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_field_int;
CREATE TABLE tracker_field_int(
    field_id INT(11) NOT NULL PRIMARY KEY,
    default_value INT(11) NULL,
    maxchars INT(11) NOT NULL,
    size INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_float;
CREATE TABLE tracker_field_float(
    field_id INT(11) NOT NULL PRIMARY KEY,
    default_value FLOAT(10,4) NULL,
    maxchars INT(11) NOT NULL,
    size INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_text;
CREATE TABLE tracker_field_text(
    field_id INT(11) NOT NULL PRIMARY KEY,
    default_value TEXT NULL,
    rows INT(11) NOT NULL,
    cols INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_string;
CREATE TABLE tracker_field_string(
    field_id INT(11) NOT NULL PRIMARY KEY,
    default_value TEXT NULL,
    maxchars INT(11) NOT NULL,
    size INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_msb;
CREATE TABLE tracker_field_msb(
    field_id INT(11) NOT NULL PRIMARY KEY,
    size INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_date;
CREATE TABLE tracker_field_date(
    field_id INT(11) NOT NULL PRIMARY KEY,
    default_value INT(11) NULL,
    default_value_type TINYINT(1) NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_list;
CREATE TABLE tracker_field_list(
    field_id INT(11) NOT NULL PRIMARY KEY,
    bind_type VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_openlist;
CREATE TABLE tracker_field_openlist(
    field_id INT(11) NOT NULL PRIMARY KEY,
    hint VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_computed;
CREATE TABLE tracker_field_computed (
    field_id INT(11) NOT NULL PRIMARY KEY,
    target_field_name VARCHAR(255) NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_openlist_value;
CREATE TABLE tracker_field_openlist_value(
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    field_id INT(11) UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL DEFAULT '',
    INDEX idx_search(field_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_field_list_bind_users;
CREATE TABLE tracker_field_list_bind_users(
    field_id INT(11) NOT NULL PRIMARY KEY,
    value_function TEXT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_list_bind_static;
CREATE TABLE tracker_field_list_bind_static(
    field_id INT(11) NOT NULL PRIMARY KEY,
    is_rank_alpha TINYINT(1) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_list_bind_ugroups_value;
CREATE TABLE tracker_field_list_bind_ugroups_value(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    field_id INT(11) NOT NULL,
    ugroup_id INT(11) NOT NULL,
    is_hidden TINYINT(1) NOT NULL DEFAULT '0',
    UNIQUE KEY idx(field_id, ugroup_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_list_bind_defaultvalue;
CREATE TABLE tracker_field_list_bind_defaultvalue(
    field_id INT(11) NOT NULL,
    value_id INT(11) NOT NULL,
    PRIMARY KEY default_idx(field_id, value_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_list_bind_static_value;
CREATE TABLE tracker_field_list_bind_static_value(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    old_id INT(11) NULL,
    field_id INT(11) NOT NULL,
    label VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    rank INT(11) NOT NULL,
    is_hidden TINYINT(1) NOT NULL,
    original_value_id INT(11) NOT NULL DEFAULT '0',
    INDEX field_id_idx(field_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset;
CREATE TABLE tracker_changeset(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    artifact_id INT(11) NOT NULL,
    submitted_by INT(11) NULL,
    submitted_on INT(11) NOT NULL,
    email VARCHAR(255) NULL,
    INDEX artifact_idx(artifact_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_comment;
CREATE TABLE tracker_changeset_comment(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    changeset_id INT(11) NOT NULL,
    comment_type_id INT(11) NULL,
    canned_response_id INT(11) NULL,
    parent_id INT(11) NULL,
    submitted_by INT(11) NULL,
    submitted_on INT(11) NOT NULL,
    body TEXT NOT NULL,
    body_format varchar(16) NOT NULL default 'text',
    old_artifact_history_id INT(11) NULL,
    INDEX changeset_idx(changeset_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_value;
CREATE TABLE tracker_changeset_value(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    changeset_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    has_changed TINYINT(1) NOT NULL,
    INDEX value_idx(changeset_id, field_id),
    INDEX field_idx(field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_value_file;
CREATE TABLE tracker_changeset_value_file(
    changeset_value_id INT(11) NOT NULL,
    fileinfo_id INT(11) NOT NULL,
    PRIMARY KEY(changeset_value_id, fileinfo_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_int;
CREATE TABLE tracker_changeset_value_int(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value INT(11) NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_float;
CREATE TABLE tracker_changeset_value_float(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value FLOAT(10,4) NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_text;
CREATE TABLE tracker_changeset_value_text(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_date;
CREATE TABLE tracker_changeset_value_date(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value INT(11) NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_list;
CREATE TABLE tracker_changeset_value_list(
    changeset_value_id INT(11) NOT NULL,
    bindvalue_id INT(11) NOT NULL,
    PRIMARY KEY idx(changeset_value_id, bindvalue_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_openlist;
CREATE TABLE tracker_changeset_value_openlist(
    changeset_value_id INT(11) NOT NULL,
    bindvalue_id INT(11) NULL,
    openvalue_id INT(11) NULL,
    insertion_order INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    UNIQUE idx(changeset_value_id, bindvalue_id, openvalue_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_artifactlink;
CREATE TABLE tracker_changeset_value_artifactlink(
    changeset_value_id INT(11) NOT NULL,
    artifact_id INT(11) NOT NULL,
    keyword VARCHAR(32) NOT NULL,
    group_id INT(11) NOT NULL,
    PRIMARY KEY(changeset_value_id, artifact_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_value_permissionsonartifact;
CREATE TABLE tracker_changeset_value_permissionsonartifact(
  changeset_value_id int(11) NOT NULL,
  use_perm tinyint(1) NOT NULL,
  ugroup_id int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_fileinfo;
CREATE TABLE tracker_fileinfo(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    old_id INT(11) NULL,
    submitted_by INT(11) NOT NULL,
    description TEXT NULL,
    filename TEXT NOT NULL,
    filesize BIGINT UNSIGNED NOT NULL,
    filetype TEXT NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_report;
CREATE TABLE tracker_report(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    old_id INT(11) NULL,
    project_id INT(11) NULL,
    user_id INT(11) NULL,
    tracker_id INT(11) NOT NULL,
    is_default TINYINT(1) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    current_renderer_id INT(11) NOT NULL,
    parent_report_id INT(11) NULL,
    is_query_displayed TINYINT(1) NOT NULL,
    updated_by int(11) NULL,
    updated_at int(11) NULL,
    INDEX tracker_idx(tracker_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_report_renderer;
CREATE TABLE tracker_report_renderer(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    old_id INT(11) NULL,
    report_id INT(11) NOT NULL,
    renderer_type VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    rank INT(11) NOT NULL,
    INDEX report_idx(report_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_report_renderer_table;
CREATE TABLE tracker_report_renderer_table(
    renderer_id INT(11) NOT NULL PRIMARY KEY,
    chunksz MEDIUMINT NOT NULL,
    multisort TINYINT(1) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_renderer_table_sort;
CREATE TABLE tracker_report_renderer_table_sort(
    renderer_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    is_desc TINYINT(1) NOT NULL,
    rank INT(11) NOT NULL,
    PRIMARY KEY sort_idx(renderer_id, field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_renderer_table_columns;
CREATE TABLE tracker_report_renderer_table_columns(
    renderer_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    rank INT(11) NOT NULL,
    width TINYINT NOT NULL,
    PRIMARY KEY column_idx(renderer_id, field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_renderer_table_functions_aggregates;
CREATE TABLE tracker_report_renderer_table_functions_aggregates(
    renderer_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    aggregate VARCHAR(10) NOT NULL,
    PRIMARY KEY aggreg_idx(renderer_id, field_id, aggregate(10))
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_criteria;
CREATE TABLE tracker_report_criteria(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    report_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    rank INT(11) NOT NULL,
    is_advanced TINYINT(1) NOT NULL,
    INDEX report_idx(report_id),
    INDEX report_field_idx(report_id, field_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_report_criteria_date_value;
CREATE TABLE tracker_report_criteria_date_value(
    criteria_id INT(11) NOT NULL PRIMARY KEY,
    op CHAR(1) NULL,
    from_date INT(11) NULL,
    to_date INT(11) NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_criteria_alphanum_value;
CREATE TABLE tracker_report_criteria_alphanum_value(
    criteria_id INT(11) NOT NULL PRIMARY KEY,
    value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_criteria_file_value;
CREATE TABLE tracker_report_criteria_file_value(
    criteria_id INT(11) NOT NULL PRIMARY KEY,
    value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_criteria_list_value;
CREATE TABLE tracker_report_criteria_list_value(
    criteria_id INT(11) NOT NULL,
    value INT(11) NOT NULL,
    PRIMARY KEY value_idx(criteria_id, value)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_criteria_openlist_value;
CREATE TABLE tracker_report_criteria_openlist_value(
    criteria_id INT(11) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS  tracker_report_criteria_permissionsonartifact_value;
CREATE TABLE IF NOT EXISTS tracker_report_criteria_permissionsonartifact_value(
  criteria_id int(11) NOT NULL,
  value int(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS  tracker_field_list_bind_decorator;
CREATE TABLE tracker_field_list_bind_decorator(
    field_id INT(11) NOT NULL,
    value_id INT(11) NOT NULL,
    red TINYINT UNSIGNED NOT NULL,
    green TINYINT UNSIGNED NOT NULL,
    blue TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY idx(field_id, value_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS  tracker_artifact;
CREATE TABLE tracker_artifact(
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tracker_id int(11) NOT NULL,
  last_changeset_id INT(11) NOT NULL,
  submitted_by INT(11) NOT NULL,
  submitted_on INT(11) NOT NULL,
  use_artifact_permissions tinyint(1) NOT NULL default '0',
  INDEX idx_tracker_id (tracker_id),
  INDEX idx_my (submitted_by, tracker_id, last_changeset_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_artifact_priority;
CREATE TABLE tracker_artifact_priority(
    curr_id int(11) NULL,
    succ_id int(11) NULL,
    rank    int(11) NOT NULL,
    UNIQUE idx(curr_id, succ_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS  tracker_tooltip;
CREATE TABLE tracker_tooltip(
    tracker_id INT(11) NOT NULL ,
    field_id INT(11) NOT NULL ,
    rank INT(11) NOT NULL ,
    PRIMARY KEY idx(tracker_id, field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS   tracker_global_notification;
CREATE TABLE tracker_global_notification(
    id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tracker_id int(11) NOT NULL ,
    addresses text NOT NULL ,
    all_updates tinyint(1) NOT NULL ,
    check_permissions tinyint(1) NOT NULL ,
    INDEX tracker_id(tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS    tracker_watcher;
CREATE TABLE tracker_watcher(
    user_id int(11) NOT NULL default '0',
    watchee_id int(11) NOT NULL default '0',
    tracker_id int(11) NOT NULL default '0',
    KEY watchee_id_idx(watchee_id, tracker_id) ,
    KEY user_id_idx(user_id , tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_notification_role;
CREATE TABLE tracker_notification_role(
    role_id int(11) NOT NULL, 
    tracker_id int(11) NOT NULL, 
    role_label VARCHAR(255) NULL, 
    rank int(11) NOT NULL, 
    short_description_msg VARCHAR(255) NULL, 
    description_msg VARCHAR(255) NULL,
    INDEX role_id_idx(role_id),
    INDEX tracker_id_idx(tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_notification_event;
CREATE TABLE  tracker_notification_event(
    event_id int(11) NOT NULL, 
    tracker_id int(11) NOT NULL, 
    event_label VARCHAR(255) NULL, 
    rank int(11) NOT NULL, 
    short_description_msg VARCHAR(255) NULL, 
    description_msg VARCHAR(255) NULL,
    INDEX event_id_idx(event_id),
    INDEX tracker_id_idx(tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_notification;
CREATE TABLE  tracker_notification(
    user_id int(11) NOT NULL, 
    tracker_id int(11) NOT NULL, 
    role_id int(11) NOT NULL, 
    event_id int(11) NOT NULL, 
    notify int(11) NOT NULL DEFAULT 1,
    INDEX user_id_idx(user_id),
    INDEX tracker_id_idx(tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_notification_role_default;
CREATE TABLE  tracker_notification_role_default(
    role_id int(11) NOT NULL, 
    role_label VARCHAR(255) NULL, 
    rank int(11) NOT NULL, 
    short_description_msg VARCHAR(255) NULL, 
    description_msg VARCHAR(255) NULL,
    INDEX role_id_idx(role_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_notification_event_default;
CREATE TABLE  tracker_notification_event_default(
    event_id int(11) NOT NULL, 
    event_label VARCHAR(255) NULL, 
    rank int(11) NOT NULL, 
    short_description_msg VARCHAR(255) NULL, 
    description_msg VARCHAR(255) NULL,
    INDEX event_id_idx(event_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS  tracker_canned_response;
CREATE TABLE tracker_canned_response(
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, 
    tracker_id INT(11) NOT NULL, 
    title TEXT NOT NULL, 
    body TEXT NOT NULL,
    INDEX tracker_id_idx(tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_staticfield_richtext;
CREATE TABLE tracker_staticfield_richtext(
  field_id int(11) NOT NULL,
  static_value text default NULL,
  PRIMARY KEY  (field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_title;
CREATE TABLE tracker_semantic_title (
    tracker_id INT(11) NOT NULL PRIMARY KEY,
    field_id INT(11) NOT NULL,
    INDEX filed_id_idx(field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_status;
CREATE TABLE tracker_semantic_status (
    tracker_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    open_value_id INT(11) NOT NULL,
    INDEX idx(tracker_id, field_id, open_value_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_contributor;
CREATE TABLE tracker_semantic_contributor (
  tracker_id int(11) NOT NULL PRIMARY KEY,
  field_id int(11) NOT NULL,
  INDEX filed_id_idx(field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_perm;
CREATE TABLE tracker_perm (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tracker_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  perm_level int(11) NOT NULL default '0',
  UNIQUE KEY unique_user(tracker_id, user_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_rule;
CREATE TABLE IF NOT EXISTS tracker_rule(
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  tracker_id int(11) unsigned NOT NULL default '0',
  rule_type tinyint(4) unsigned NOT NULL default '0',
  KEY tracker_id (tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_rule_list;
CREATE TABLE IF NOT EXISTS tracker_rule_list(
  tracker_rule_id int(11) unsigned NOT NULL PRIMARY KEY,
  source_field_id int(11) unsigned NOT NULL default '0',
  source_value_id int(11) unsigned NOT NULL default '0',
  target_field_id int(11) unsigned NOT NULL default '0',
  target_value_id int(11) unsigned default NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_rule_date;
CREATE TABLE IF NOT EXISTS tracker_rule_date(
  tracker_rule_id int(11) unsigned NOT NULL PRIMARY KEY,
  source_field_id int(11) unsigned NOT NULL,
  target_field_id int(11) unsigned NOT NULL,
  comparator varchar(2) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_hierarchy;
CREATE TABLE IF NOT EXISTS tracker_hierarchy (
  parent_id int(11) NOT NULL,
  child_id int(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_reminder;
CREATE TABLE tracker_reminder (
    reminder_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    tracker_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    ugroups VARCHAR(255) NOT NULL,
    notification_type TINYINT(1) DEFAULT 0,
    distance INT( 11 ) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    PRIMARY KEY (reminder_id),
    UNIQUE KEY (tracker_id, field_id, ugroups, notification_type, distance, status)
) ENGINE=InnoDB;

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_tracker:service_lbl_key', 'plugin_tracker:service_desc_key', 'plugin_tracker', '/plugins/tracker/?group_id=$group_id', 1, 1, 'system', 151);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_tracker:service_lbl_key' , 'plugin_tracker:service_desc_key' , 'plugin_tracker', CONCAT('/plugins/tracker/?group_id=', group_id), 1 , 0 , 'system',  151
FROM service
WHERE group_id != 100;


INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PLUGIN_TRACKER_ACCESS_FULL',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_FULL',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_FULL',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_FULL',4);


INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_SUBMITTER',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_SUBMITTER',4);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_ASSIGNEE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ACCESS_ASSIGNEE',4);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_SUBMIT',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_SUBMIT',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_SUBMIT',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_SUBMIT',4);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_READ',4);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_UPDATE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_FIELD_UPDATE',4);

INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PLUGIN_TRACKER_ARTIFACT_ACCESS',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ARTIFACT_ACCESS',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ARTIFACT_ACCESS',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_ARTIFACT_ACCESS',4);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_WORKFLOW_TRANSITION',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_WORKFLOW_TRANSITION',2);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('PLUGIN_TRACKER_WORKFLOW_TRANSITION',3,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('PLUGIN_TRACKER_WORKFLOW_TRANSITION',4);

INSERT INTO tracker_artifact_priority (curr_id, succ_id, rank) VALUES (NULL, NULL, 0);
