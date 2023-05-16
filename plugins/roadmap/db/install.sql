CREATE TABLE IF NOT EXISTS plugin_roadmap_widget (
    id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
    owner_id int(11) unsigned NOT NULL default '0',
    owner_type varchar(1) NOT NULL default 'u',
    title TEXT NOT NULL,
    default_timescale VARCHAR(255) NOT NULL default 'month',
    tracker_id INT(11) NOT NULL,
    lvl1_iteration_tracker_id INT(11) NULL,
    lvl2_iteration_tracker_id INT(11) NULL,
    KEY (owner_id, owner_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_roadmap_widget_trackers (
    plugin_roadmap_widget_id INT(11) UNSIGNED NOT NULL,
    tracker_id INT(11) NOT NULL,
    PRIMARY KEY (plugin_roadmap_widget_id, tracker_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_roadmap_widget_filter (
    widget_id INT UNSIGNED NOT NULL PRIMARY KEY,
    report_id INT NOT NULL,
    INDEX report_id_idx(report_id)
) ENGINE=InnoDB;
