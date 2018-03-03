CREATE TABLE plugin_timesheeting_enabled_trackers (
  tracker_id INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE plugin_timesheeting_writers (
  tracker_id INT(11) NOT NULL,
  ugroup_id INT(11) NOT NULL,
  PRIMARY KEY (tracker_id, ugroup_id),
  INDEX ugroup_idx (ugroup_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_timesheeting_readers (
  tracker_id INT(11) NOT NULL,
  ugroup_id INT(11) NOT NULL,
  PRIMARY KEY (tracker_id, ugroup_id),
  INDEX ugroup_idx (ugroup_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_timesheeting_times (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  user_id INT(11) NOT NULL,
  artifact_id INT(11) NOT NULL,
  minutes SMALLINT(6) UNSIGNED NOT NULL,
  step VARCHAR(255) NOT NULL,
  day DATE NOT NULL,
  UNIQUE (user_id, artifact_id, day)
) ENGINE=InnoDB;