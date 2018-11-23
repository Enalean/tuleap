CREATE TABLE plugin_gitlfs_authorization_action (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  verifier VARCHAR(255) NOT NULL,
  expiration_date INT(11) UNSIGNED NOT NULL,
  repository_id INT(10) UNSIGNED NOT NULL,
  action_type VARCHAR(16) NOT NULL,
  object_oid CHAR(64) NOT NULL,
  object_size INT(11) UNSIGNED NOT NULL,
  INDEX idx_expiration_date (expiration_date)
);