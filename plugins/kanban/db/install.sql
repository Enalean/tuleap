DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_configuration (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    tracker_id INT(11) NOT NULL,
    name VARCHAR(255) NOT NULL
);

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration_column;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_kanban_configuration_column (
    kanban_id INT(11) NOT NULL,
    value_id INT(11) NOT NULL,
    wip_limit INT(11) UNSIGNED,
    PRIMARY KEY(kanban_id, value_id)
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

DROP TABLE IF EXISTS plugin_agiledashboard_kanban_recently_visited;
CREATE TABLE plugin_agiledashboard_kanban_recently_visited (
    user_id INT(11) NOT NULL,
    kanban_id INT(11) NOT NULL,
    created_on INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY(user_id, kanban_id),
    INDEX idx_user_visit_time(user_id, created_on)
) ENGINE=InnoDB;

INSERT INTO service(`group_id`, `label`, `description`, `short_name`, `link`, `is_active`, `is_used`, `scope`, `rank`)
SELECT DISTINCT service.group_id,'label','','plugin_kanban',NULL,1,0,'system',154
FROM service
WHERE short_name != 'plugin_kanban';
