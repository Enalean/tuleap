CREATE TABLE plugin_timetracking_enabled_trackers (
  tracker_id INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE plugin_timetracking_writers (
  tracker_id INT(11) NOT NULL,
  ugroup_id INT(11) NOT NULL,
  PRIMARY KEY (tracker_id, ugroup_id),
  INDEX ugroup_idx (ugroup_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_timetracking_readers (
  tracker_id INT(11) NOT NULL,
  ugroup_id INT(11) NOT NULL,
  PRIMARY KEY (tracker_id, ugroup_id),
  INDEX ugroup_idx (ugroup_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_timetracking_times (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  artifact_id INT(11) NOT NULL,
  minutes SMALLINT(6) UNSIGNED NOT NULL,
  step VARCHAR(255) NOT NULL,
  day DATE NOT NULL,
  INDEX time (user_id, artifact_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_timetracking_overview_widget (
    id INT(11) AUTO_INCREMENT NOT NULL PRIMARY KEY,
    widget_title VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_timetracking_overview_report_tracker (
    report_id INT(11) NOT NULL,
    tracker_id INT(11) NOT NULL,
    PRIMARY KEY (report_id, tracker_id)
) ENGINE=InnoDB;
