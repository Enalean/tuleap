CREATE TABLE plugin_timesheeting_enabled_trackers (
  tracker_id INT(11) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE plugin_timesheeting_writers (
  tracker_id INT(11) NOT NULL,
  ugroup_id INT(11) NOT NULL,
  PRIMARY KEY (tracker_id, ugroup_id),
  INDEX ugroup_idx (ugroup_id)
) ENGINE=InnoDB;