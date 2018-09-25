DROP TABLE IF EXISTS plugin_create_test_env_bot;
CREATE TABLE plugin_create_test_env_bot (
  bot_id int(11) UNSIGNED NOT NULL,
  UNIQUE bot_id_idx(bot_id)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_create_test_env_activity;
CREATE TABLE plugin_create_test_env_activity (
  user_id INT(11) UNSIGNED NOT NULL,
  project_id INT(11) UNSIGNED NOT NULL,
  service VARCHAR(64) DEFAULT '',
  action  TEXT,
  time    INT(11) UNSIGNED NOT NULL,
  INDEX idx_time(time)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_callmeback_email;
CREATE TABLE plugin_callmeback_email (
  email_to varchar(255) NOT NULL,
  PRIMARY KEY (email_to)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_callmeback_messages;
CREATE TABLE plugin_callmeback_messages (
  language_id varchar(10) NOT NULL,
  message varchar(255),
  PRIMARY KEY (language_id)
) ENGINE=InnoDB;

INSERT INTO plugin_callmeback_messages VALUES
  ('en_US', ''),
  ('fr_FR', '');
