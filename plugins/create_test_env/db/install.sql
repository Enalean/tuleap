DROP TABLE IF EXISTS plugin_create_test_env_activity;
CREATE TABLE plugin_create_test_env_activity (
  user_id INT(11) UNSIGNED NOT NULL,
  project_id INT(11) UNSIGNED NOT NULL,
  service VARCHAR(64) DEFAULT '',
  action  TEXT,
  time    INT(11) UNSIGNED NOT NULL,
  INDEX idx_time(time)
) ENGINE=InnoDB;
