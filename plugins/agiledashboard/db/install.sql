CREATE TABLE IF NOT EXISTS plugin_agiledashboard_planning(
    id int(11) NOT NULL auto_increment,
    name varchar(255) NOT NULL,
    group_id INT( 11 ) UNSIGNED NOT NULL,
    planning_tracker_id INT(11) NOT NULL,
    backlog_title varchar(255) NOT NULL,
    plan_title varchar(255) NOT NULL,
    KEY idx(id, planning_tracker_id)
);

CREATE TABLE IF NOT EXISTS plugin_agiledashboard_planning_backlog_tracker(
    planning_id int(11) NOT NULL,
    tracker_id int(11) NOT NULL,
    KEY idx(planning_id, tracker_id)
);

INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
VALUES ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE', 2, 0),
       ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE', 3, 1),
       ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE', 4, 0);

DROP TABLE IF EXISTS plugin_agiledashboard_semantic_initial_effort;
CREATE TABLE plugin_agiledashboard_semantic_initial_effort (
    tracker_id INT(11) PRIMARY KEY,
    field_id INT(11) NOT NULL,
    INDEX field_id_idx(field_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_cardwall_semantic_cardfields;
CREATE TABLE plugin_cardwall_semantic_cardfields (
    id int(11) AUTO_INCREMENT NOT NULL PRIMARY KEY,
    tracker_id INT(11),
    field_id INT(11) NOT NULL,
    `rank` int(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_criteria;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_criteria (
    report_id INT(11) PRIMARY KEY,
    milestone_id INT(11) NOT NULL
);

DROP TABLE IF EXISTS plugin_agiledashboard_configuration;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_configuration (
    project_id INT(11) PRIMARY KEY,
    scrum TINYINT NOT NULL DEFAULT 1,
    scrum_title VARCHAR(255) NOT NULL DEFAULT 'Scrum'
);

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_configuration (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    tracker_id INT(11) NOT NULL,
    is_promoted BOOL DEFAULT FALSE,
    name VARCHAR(255) NOT NULL
);

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration_column;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_configuration_column (
    kanban_id INT(11) NOT NULL,
    value_id INT(11) NOT NULL,
    wip_limit INT(11) UNSIGNED,
    PRIMARY KEY(kanban_id, value_id)
);

DROP TABLE IF EXISTS plugin_agiledashboard_scrum_mono_milestones;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_scrum_mono_milestones (
    project_id INT(11) NOT NULL PRIMARY KEY
);

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_widget;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_widget (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  kanban_id int(11) NOT NULL,
  KEY (owner_id, owner_type)
);

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_widget_config;
CREATE TABLE plugin_agiledashboard_kanban_widget_config(
    widget_id int(11),
    tracker_report_id int(11) NOT NULL,
    PRIMARY KEY (widget_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_tracker_reports;
CREATE TABLE plugin_agiledashboard_kanban_tracker_reports (
  kanban_id INT(11) NOT NULL,
  report_id INT(11) NOT NULL,
  PRIMARY KEY(kanban_id, report_id),
  INDEX kanban_tracker_reports_report_idx(report_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_agiledashboard_tracker_field_burnup_cache (
  artifact_id  INT(11) NOT NULL,
  timestamp    INT(11) NOT NULL,
  total_effort FLOAT(10,4) NULL,
  team_effort  FLOAT(10,4) NULL,
  UNIQUE KEY time_at_field (artifact_id, timestamp)
) ENGINE=InnoDB;

CREATE TABLE plugin_agiledashboard_tracker_field_burnup_cache_subelements (
  artifact_id  INT(11) NOT NULL,
  timestamp    INT(11) NOT NULL,
  total_subelements INT(11) NULL,
  closed_subelements  INT(11) NULL,
  UNIQUE KEY time_at_field (artifact_id, timestamp)
) ENGINE=InnoDB;

CREATE TABLE plugin_agiledashboard_burnup_projects_count_mode (
  project_id  INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_recently_visited;
CREATE TABLE plugin_agiledashboard_kanban_recently_visited (
    user_id INT(11) NOT NULL,
    kanban_id INT(11) NOT NULL,
    created_on INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(user_id, kanban_id),
    INDEX idx_user_visit_time(user_id, created_on)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_planning_explicit_backlog_usage;
CREATE TABLE plugin_agiledashboard_planning_explicit_backlog_usage (
   project_id INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_planning_artifacts_explicit_backlog;
CREATE TABLE plugin_agiledashboard_planning_artifacts_explicit_backlog (
   project_id INT(11) NOT NULL,
   artifact_id INT(11) NOT NULL,
   PRIMARY KEY(project_id, artifact_id),
   INDEX idx_project_id(project_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_tracker_workflow_action_add_top_backlog;
CREATE TABLE plugin_agiledashboard_tracker_workflow_action_add_top_backlog (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    transition_id INT(11) NOT NULL,
    INDEX idx_wf_transition_id(transition_id)
) ENGINE=InnoDB;

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
       VALUES      ( 100, 'plugin_agiledashboard:service_lbl_key', 'plugin_agiledashboard:service_desc_key', 'plugin_agiledashboard', '/plugins/agiledashboard/?group_id=$group_id', 1, 0, 'system', 152);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
SELECT DISTINCT group_id , 'plugin_agiledashboard:service_lbl_key' , 'plugin_agiledashboard:service_desc_key' , 'plugin_agiledashboard', CONCAT('/plugins/agiledashboard/?group_id=', group_id), 1 , 0 , 'system',  152
FROM service
WHERE group_id != 100;
