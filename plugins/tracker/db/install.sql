--
--  Table structure for workflow_tracker
--
DROP TABLE IF EXISTS tracker_workflow;
CREATE TABLE IF NOT EXISTS tracker_workflow (
  workflow_id int(11) NOT NULL auto_increment  PRIMARY KEY,
  tracker_id int(11) NOT NULL,
  field_id int(11) NOT NULL,
  is_used tinyint(1) NOT NULL,
  is_legacy tinyint(1) NOT NULL DEFAULT 0,
  is_advanced tinyint(1) NOT NULL,
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
  INDEX idx_wf_workflow_id(workflow_id, transition_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_workflow_transition_condition_field_notempty;
CREATE TABLE  tracker_workflow_transition_condition_field_notempty(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    transition_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_workflow_transition_condition_comment_notempty;
CREATE TABLE  tracker_workflow_transition_condition_comment_notempty(
    transition_id INT(11) NOT NULL PRIMARY KEY,
    is_comment_required TINYINT(1) NOT NULL
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
  value DOUBLE default NULL,
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
    log_priority_changes TINYINT(1) NOT NULL default '0',
    notifications_level INT( 11 ) NOT NULL default '0',
    from_tv3_id INT(11) NULL,
    color varchar(64) NOT NULL DEFAULT 'inca-silver',
    enable_emailgateway TINYINT(1) NOT NULL DEFAULT '0',
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
    `rank` INT( 11 ) UNSIGNED NOT NULL,
    scope CHAR( 1 ) NOT NULL,
    required TINYINT(1) NULL,
    notifications TINYINT(1) NULL,
    original_field_id INT( 11 ) UNSIGNED NOT NULL DEFAULT '0',
    INDEX idx_fk_old_id( old_id ),
    INDEX idx_fk_tracker_id( tracker_id ),
    INDEX idx_fk_parent_id( parent_id ),
    INDEX idx_original_field_id(original_field_id)
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
    default_value DOUBLE NULL,
    maxchars INT(11) NOT NULL,
    size INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_text;
CREATE TABLE tracker_field_text(
    field_id INT(11) NOT NULL PRIMARY KEY,
    default_value TEXT NULL,
    `rows` INT(11) NOT NULL,
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
    default_value_type TINYINT(1) NULL,
    display_time TINYINT DEFAULT 0
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
    default_value DOUBLE NULL,
    target_field_name VARCHAR(255) NULL,
    fast_compute TINYINT DEFAULT 0
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_field_openlist_value;
CREATE TABLE tracker_field_openlist_value(
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    field_id INT(11) UNSIGNED NOT NULL,
    label VARCHAR(255) NOT NULL DEFAULT '',
    is_hidden BOOL DEFAULT FALSE,
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
    `rank` INT(11) NOT NULL,
    is_hidden TINYINT(1) NOT NULL,
    original_value_id INT(11) NOT NULL DEFAULT '0',
    INDEX idx_original_value_id (original_value_id, id),
    INDEX idx_bind_value_field_id(field_id, id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

CREATE TABLE IF NOT EXISTS tracker_field_burndown (
    field_id INT(11) NOT NULL PRIMARY KEY,
    use_cache TINYINT DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tracker_field_computed_cache (
    artifact_id INT(11) NOT NULL,
    field_id    INT(11) NOT NULL,
    timestamp   INT(11) NOT NULL,
    value       DOUBLE NULL,
    UNIQUE KEY time_at_field (artifact_id, field_id, timestamp)
) ENGINE=InnoDB;

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

DROP TABLE IF EXISTS tracker_changeset_comment_fulltext;
CREATE TABLE tracker_changeset_comment_fulltext(
    comment_id INT(11) NOT NULL PRIMARY KEY,
    stripped_body TEXT DEFAULT NULL,
    FULLTEXT stripped_body_idx(stripped_body)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_incomingmail;
CREATE TABLE tracker_changeset_incomingmail(
    changeset_id INT(11) NOT NULL PRIMARY KEY,
    raw_mail TEXT NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_changeset_from_xml;
CREATE TABLE plugin_tracker_changeset_from_xml(
   changeset_id INT(11) NOT NULL PRIMARY KEY,
   user_id INT(11) NOT NULL,
   timestamp INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_value;
CREATE TABLE tracker_changeset_value(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    changeset_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    has_changed TINYINT(1) NOT NULL,
    INDEX value_idx(changeset_id, field_id),
    INDEX idx_value_field_id(field_id, id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_value_file;
CREATE TABLE tracker_changeset_value_file(
    changeset_value_id INT(11) NOT NULL,
    fileinfo_id INT(11) NOT NULL,
    PRIMARY KEY(changeset_value_id, fileinfo_id),
    INDEX reverse_idx (fileinfo_id, changeset_value_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_int;
CREATE TABLE tracker_changeset_value_int(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value INT(11) NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_float;
CREATE TABLE tracker_changeset_value_float(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value DOUBLE NULL
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_text;
CREATE TABLE tracker_changeset_value_text(
    changeset_value_id INT(11) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL,
    body_format varchar(16) NOT NULL default 'text'
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
    PRIMARY KEY idx(changeset_value_id, bindvalue_id),
    INDEX idx_bind (bindvalue_id, changeset_value_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_openlist;
CREATE TABLE tracker_changeset_value_openlist(
    changeset_value_id INT(11) NOT NULL,
    bindvalue_id INT(11) NULL,
    openvalue_id INT(11) NULL,
    insertion_order INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    UNIQUE idx(changeset_value_id, bindvalue_id, openvalue_id),
    INDEX idx_bindvalue_id(bindvalue_id, changeset_value_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_artifactlink;
CREATE TABLE tracker_changeset_value_artifactlink(
    changeset_value_id INT(11) NOT NULL,
    nature VARCHAR(255) NULL,
    artifact_id INT(11) NOT NULL,
    keyword VARCHAR(32) NOT NULL,
    group_id INT(11) NOT NULL,
    PRIMARY KEY(changeset_value_id, artifact_id),
    INDEX idx_reverse (artifact_id, changeset_value_id, nature(10)),
    INDEX idx_nature (nature(10)),
    INDEX idx_group_id_keyword (group_id, keyword)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_changeset_value_permissionsonartifact;
CREATE TABLE tracker_changeset_value_permissionsonartifact(
  changeset_value_id int(11) NOT NULL,
  use_perm tinyint(1) NOT NULL,
  ugroup_id int(11) NOT NULL,
  INDEX idx_changeset_value_id (changeset_value_id)
) ENGINE=InnoDB AUTO_INCREMENT=101;

DROP TABLE IF EXISTS tracker_changeset_value_computedfield_manual_value;
CREATE TABLE tracker_changeset_value_computedfield_manual_value (
    changeset_value_id INT(11) NOT NULL,
    value DOUBLE,
    PRIMARY KEY(changeset_value_id)
) ENGINE=InnoDB;

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

DROP TABLE IF EXISTS tracker_report_config;
CREATE TABLE tracker_report_config(
    query_limit INT(1) NOT NULL DEFAULT 30
);

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
    is_in_expert_mode TINYINT(1) NOT NULL DEFAULT 0,
    expert_query TEXT NOT NULL,
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
    `rank` INT(11) NOT NULL,
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
    `rank` INT(11) NOT NULL,
    PRIMARY KEY sort_idx(renderer_id, field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_renderer_table_columns;
CREATE TABLE tracker_report_renderer_table_columns(
    renderer_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    artlink_nature VARCHAR(255) NULL,
    artlink_nature_format VARCHAR(255) NULL,
    `rank` INT(11) NOT NULL,
    width TINYINT NOT NULL,
    INDEX column_idx(renderer_id, field_id)
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
    `rank` INT(11) NOT NULL,
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
    red TINYINT UNSIGNED NULL,
    green TINYINT UNSIGNED NULL,
    blue TINYINT UNSIGNED NULL,
    tlp_color_name VARCHAR (30) NULL,
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
  per_tracker_artifact_id INT(11) NOT NULL,
  INDEX idx_tracker_id (tracker_id),
  INDEX idx_my (submitted_by, tracker_id, last_changeset_id),
  INDEX idx_id_changeset_id(id, last_changeset_id),
  INDEX idx_changeset_tracker(last_changeset_id, tracker_id),
  INDEX idx_submitted_on(submitted_on)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_artifact_pending_indexation;
CREATE TABLE plugin_tracker_artifact_pending_indexation(
    id int(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_artifact_pending_removal;
CREATE TABLE plugin_tracker_artifact_pending_removal LIKE tracker_artifact;

DROP TABLE IF EXISTS tracker_artifact_priority_rank;
CREATE TABLE tracker_artifact_priority_rank(
    artifact_id INT(11) PRIMARY KEY,
    `rank` INT(11) UNSIGNED NOT NULL,
    INDEX idx_rank(`rank`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_artifact_priority_history;
CREATE TABLE tracker_artifact_priority_history(
  id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  moved_artifact_id INT(11) NOT NULL,
  artifact_id_higher INT(11) NULL,
  artifact_id_lower INT(11) NULL,
  context INT(11) NULL,
  project_id INT(11) NULL,
  has_been_raised TINYINT(1) NULL,
  prioritized_by INT(11) NOT NULL,
  prioritized_on INT(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS  tracker_tooltip;
CREATE TABLE tracker_tooltip(
    tracker_id INT(11) NOT NULL ,
    field_id INT(11) NOT NULL ,
    `rank` INT(11) NOT NULL ,
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

CREATE TABLE IF NOT EXISTS tracker_global_notification_users (
    notification_id INT(11) UNSIGNED NOT NULL,
    user_id INT(11) NOT NULL,
    PRIMARY KEY (notification_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tracker_global_notification_ugroups (
    notification_id INT(11) UNSIGNED NOT NULL,
    ugroup_id INT(11) NOT NULL,
    PRIMARY KEY (notification_id, ugroup_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tracker_global_notification_unsubscribers (
    tracker_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    PRIMARY KEY (tracker_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tracker_only_status_change_notification_subscribers (
    tracker_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    PRIMARY KEY (tracker_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_tracker_involved_notification_subscribers (
   tracker_id INT(11) NOT NULL,
   user_id INT(11) NOT NULL,
   PRIMARY KEY (tracker_id, user_id)
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

DROP TABLE IF EXISTS tracker_semantic_description;
CREATE TABLE tracker_semantic_description (
    tracker_id INT(11) NOT NULL PRIMARY KEY,
    field_id INT(11) NOT NULL,
    INDEX filed_id_idx(field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_status;
CREATE TABLE tracker_semantic_status (
    tracker_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    open_value_id INT(11) NOT NULL,
    INDEX idx(tracker_id, field_id, open_value_id),
    INDEX idx_field_open(field_id, open_value_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_contributor;
CREATE TABLE tracker_semantic_contributor (
  tracker_id int(11) NOT NULL PRIMARY KEY,
  field_id int(11) NOT NULL,
  INDEX filed_id_idx(field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_timeframe;
CREATE TABLE tracker_semantic_timeframe (
  tracker_id int(11) NOT NULL PRIMARY KEY,
  start_date_field_id int(11) NULL,
  duration_field_id int(11) NULL,
  end_date_field_id int(11) NULL,
  implied_from_tracker_id int(11) NULL,
  INDEX idx_implied(implied_from_tracker_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_semantic_progress;
CREATE TABLE tracker_semantic_progress (
    tracker_id int(11) NOT NULL PRIMARY KEY,
    total_effort_field_id int(11) NULL,
    remaining_effort_field_id int(11) NULL,
    artifact_link_type TEXT NULL
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
  child_id int(11) NOT NULL PRIMARY KEY,
  INDEX idx_tracker_hierarchy_parent_id(parent_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_reminder;
CREATE TABLE tracker_reminder (
    reminder_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    tracker_id INT(11) NOT NULL,
    field_id INT(11) NOT NULL,
    ugroups VARCHAR(255) NULL,
    notification_type TINYINT(1) DEFAULT 0,
    distance INT( 11 ) DEFAULT 0,
    status TINYINT(1) DEFAULT 1,
    notify_closed_artifacts BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (reminder_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_reminder_notified_roles;
CREATE TABLE tracker_reminder_notified_roles (
    reminder_id INT(11) UNSIGNED NOT NULL,
    role_id TINYINT(1) UNSIGNED NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_workflow_trigger_rule_static_value;
CREATE TABLE tracker_workflow_trigger_rule_static_value (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    value_id INT(11) NOT NULL,
    rule_condition VARCHAR(32) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY (value_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_workflow_trigger_rule_trg_field_static_value;
CREATE TABLE tracker_workflow_trigger_rule_trg_field_static_value (
    rule_id INT(11) UNSIGNED NOT NULL,
    value_id INT(11) NOT NULL,
    INDEX idx_rule_value (rule_id, value_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_fileinfo_temporary;
CREATE TABLE IF NOT EXISTS tracker_fileinfo_temporary (
    fileinfo_id int(11) UNSIGNED NOT NULL,
    last_modified int(11) NOT NULL,
    created int(11) NOT NULL,
    tempname varchar(255) default NULL,
    offset int(11) UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_fileinfo_id ( fileinfo_id ),
    INDEX idx_last_modified( last_modified )
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_artifact_unsubscribe;
CREATE TABLE IF NOT EXISTS tracker_artifact_unsubscribe (
    artifact_id int(11) NOT NULL,
    user_id int(11) NOT NULL,
    PRIMARY KEY (artifact_id, user_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_config;
CREATE TABLE plugin_tracker_config (
    name VARCHAR(255) NOT NULL,
    value VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY idx(name(10))
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_artifactlink_natures;
CREATE TABLE plugin_tracker_artifactlink_natures (
    shortname     VARCHAR(255) NOT NULL PRIMARY KEY,
    forward_label VARCHAR(255) NOT NULL,
    reverse_label VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_notification_assigned_to;
CREATE TABLE plugin_tracker_notification_assigned_to (
    tracker_id INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_notification_email_custom_sender_format;
CREATE TABLE plugin_tracker_notification_email_custom_sender_format(
                    tracker_id int(11) NOT NULL,
                    format text,
                    enabled bool,
                    PRIMARY KEY (tracker_id),
                    FOREIGN KEY (tracker_id)
                        REFERENCES tracker(id)
                ) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_recently_visited;
CREATE TABLE plugin_tracker_recently_visited (
    user_id INT(11) NOT NULL,
    artifact_id INT(11) NOT NULL,
    created_on INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(user_id, artifact_id),
    INDEX idx_user_visit_time(user_id, created_on)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_projects_use_artifactlink_types;
CREATE TABLE plugin_tracker_projects_use_artifactlink_types (
    project_id INT(11) UNSIGNED PRIMARY KEY
) ENGINE=InnoDB;


DROP TABLE IF EXISTS plugin_tracker_projects_unused_artifactlink_types;
CREATE TABLE plugin_tracker_projects_unused_artifactlink_types (
    project_id INT(11) UNSIGNED,
    type_shortname VARCHAR(255) NOT NULL,
    PRIMARY KEY (project_id, type_shortname),
    INDEX idx_artifactlink_types_unused_project_id(project_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS tracker_report_criteria_comment_value;
CREATE TABLE tracker_report_criteria_comment_value(
    report_id INT(11) NOT NULL PRIMARY KEY,
    comment VARCHAR(255)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_deleted_artifacts;
CREATE TABLE plugin_tracker_deleted_artifacts(
    timestamp int(11) NOT NULL,
    user_id INT(11) NOT NULL,
    nb_artifacts_deleted int(2) NOT NULL,
    PRIMARY KEY (timestamp, user_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_webhook_url;
CREATE TABLE IF NOT EXISTS plugin_tracker_webhook_url (
    id int(11) unsigned PRIMARY KEY AUTO_INCREMENT,
    tracker_id int(11) NOT NULL,
    url TEXT NOT NULL,
    INDEX idx_tracker_webhook_url_tracker_id (tracker_id)
);

DROP TABLE IF EXISTS plugin_tracker_webhook_log;
CREATE TABLE IF NOT EXISTS plugin_tracker_webhook_log (
    created_on int(11) NOT NULL,
    webhook_id int(11) unsigned NOT NULL,
    status TEXT NOT NULL,
    INDEX idx(webhook_id)
);

DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_frozen_fields;
CREATE TABLE IF NOT EXISTS plugin_tracker_workflow_postactions_frozen_fields (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    transition_id INT(11) NOT NULL,
    INDEX idx_wf_transition_id( transition_id )
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_frozen_fields_value;
CREATE TABLE IF NOT EXISTS plugin_tracker_workflow_postactions_frozen_fields_value (
    postaction_id INT(11) UNSIGNED NOT NULL,
    field_id INT(11) NOT NULL,
    PRIMARY KEY (postaction_id, field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_file_upload;
CREATE TABLE plugin_tracker_file_upload (
    fileinfo_id int(11) PRIMARY KEY,
    expiration_date int(11) UNSIGNED,
    field_id int(11) NOT NULL,
    KEY idx_expiration_date(expiration_date)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_hidden_fieldsets;
CREATE TABLE IF NOT EXISTS plugin_tracker_workflow_postactions_hidden_fieldsets (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    transition_id INT(11) NOT NULL,
    INDEX idx_wf_transition_id(transition_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_workflow_postactions_hidden_fieldsets_value;
CREATE TABLE IF NOT EXISTS plugin_tracker_workflow_postactions_hidden_fieldsets_value (
    postaction_id INT(11) UNSIGNED NOT NULL,
    fieldset_id INT(11) NOT NULL,
    PRIMARY KEY (postaction_id, fieldset_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_pending_jira_import;
CREATE TABLE IF NOT EXISTS plugin_tracker_pending_jira_import (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    created_on INT(11) UNSIGNED NOT NULL,
    project_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    jira_server TEXT NOT NULL,
    jira_user_email TEXT NOT NULL,
    encrypted_jira_token BLOB NOT NULL,
    jira_project_id TEXT NOT NULL,
    jira_issue_type_name TEXT NOT NULL,
    jira_issue_type_id TEXT NOT NULL,
    tracker_name TEXT NOT NULL,
    tracker_shortname TEXT NOT NULL,
    tracker_color VARCHAR(64) NOT NULL,
    tracker_description TEXT NOT NULL,
    INDEX idx_project_id(project_id),
    INDEX idx_created_on(created_on)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_promoted;
CREATE TABLE IF NOT EXISTS plugin_tracker_promoted(
    tracker_id int(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_legacy_tracker_migrated;
CREATE TABLE IF NOT EXISTS plugin_tracker_legacy_tracker_migrated(
    legacy_tracker_id int(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_tracker_private_comment_disabled_tracker(
    tracker_id INT(11) PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_tracker_calendar_event_config(
    tracker_id INT(11) PRIMARY KEY,
    should_send_event_in_notification BOOL NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_tracker_private_comment_permission(
    comment_id INT(11) NOT NULL,
    ugroup_id int(11) NOT NULL,
    PRIMARY KEY(comment_id, ugroup_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_tracker_semantic_done;
CREATE TABLE plugin_tracker_semantic_done (
     tracker_id INT(11) NOT NULL,
     value_id INT(11) NOT NULL,
     PRIMARY KEY(tracker_id, value_id),
     INDEX semantic_done_tracker_idx(tracker_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_tracker_forbidden_move_action(
    tracker_id INT(11) PRIMARY KEY
) ENGINE=InnoDB;

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
       VALUES      ( 100, 'plugin_tracker:service_lbl_key', 'plugin_tracker:service_desc_key', 'plugin_tracker', '/plugins/tracker/?group_id=$group_id', 1, 1, 'system', 151);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
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

-- Special user for workflow management
INSERT INTO user SET
        user_id = 90,
        user_name = 'forge__tracker_workflow_manager',
        email = 'noreply@_DOMAIN_NAME_',
        realname = 'Tracker Workflow Manager',
        register_purpose = NULL,
        status = 'S',
        ldap_id = NULL,
        add_date = 370514700,
        confirm_hash = NULL,
        mail_siteupdates = 0,
        mail_va = 0,
        sticky_login = 0,
        authorized_keys = NULL,
        email_new = NULL,
        timezone = 'GMT',
        language_id = 'en_US',
        last_pwd_update = '0';

INSERT INTO user_access SET
        user_id = 90,
        last_access_date = '0';

INSERT INTO user SET
     user_id = 91,
     user_name = 'forge__tracker_importer_user',
     email = 'noreply+tracker_importer@_DOMAIN_NAME_',
     realname = 'Tracker Importer',
     register_purpose = NULL,
     status = 'S',
     ldap_id = NULL,
     add_date = 370514700,
     confirm_hash = NULL,
     mail_siteupdates = 0,
     mail_va = 0,
     sticky_login = 0,
     authorized_keys = NULL,
     email_new = NULL,
     timezone = 'GMT',
     language_id = 'en_US',
     last_pwd_update = '0';

INSERT INTO user_access SET
    user_id = 91,
    last_access_date = '0';

INSERT INTO tracker_report_config (query_limit) VALUES (30);

INSERT INTO plugin_tracker_projects_use_artifactlink_types (project_id)
SELECT DISTINCT `groups`.group_id
FROM `groups`
    INNER JOIN service USING (group_id)
WHERE `groups`.status != 'D'
      AND service.short_name = 'plugin_tracker';

INSERT INTO forgeconfig (name, value) VALUES ('tracker_jira_force_basic_auth', '1');
