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
    rank int(11) NOT NULL
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_agiledashboard_criteria;
CREATE TABLE IF NOT EXISTS plugin_agiledashboard_criteria (
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    report_id INT(11) NOT NULL,
    milestone_id INT(11) NOT NULL
);

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_agiledashboard:service_lbl_key', 'plugin_agiledashboard:service_desc_key', 'plugin_agiledashboard', '/plugins/agiledashboard/?group_id=$group_id', 1, 1, 'system', 152);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_agiledashboard:service_lbl_key' , 'plugin_agiledashboard:service_desc_key' , 'plugin_agiledashboard', CONCAT('/plugins/agiledashboard/?group_id=', group_id), 1 , 0 , 'system',  152
FROM service
WHERE group_id != 100;
