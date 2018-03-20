DROP TABLE IF EXISTS plugin_create_test_env_bot;
CREATE TABLE plugin_create_test_env_bot (
  bot_id int(11) UNSIGNED NOT NULL,
  UNIQUE bot_id_idx(bot_id)
) ENGINE=InnoDB;
