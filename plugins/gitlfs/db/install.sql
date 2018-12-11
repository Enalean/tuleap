CREATE TABLE plugin_gitlfs_authorization_action (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  verifier VARCHAR(255) NOT NULL,
  expiration_date INT(11) UNSIGNED NOT NULL,
  repository_id INT(10) UNSIGNED NOT NULL,
  action_type VARCHAR(16) NOT NULL,
  object_oid CHAR(64) NOT NULL,
  object_size INT(11) UNSIGNED NOT NULL,
  INDEX idx_expiration_date (expiration_date),
  INDEX idx_object_id (object_oid)
);

CREATE TABLE plugin_gitlfs_object (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  object_oid CHAR(64) NOT NULL,
  object_size INT(11) UNSIGNED NOT NULL,
  UNIQUE uniq_oid (object_oid)
);

CREATE TABLE plugin_gitlfs_object_repository (
  object_id INT(11) NOT NULL,
  repository_id INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (object_id, repository_id)
);

CREATE TABLE plugin_gitlfs_ssh_authorization (
  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
  verifier VARCHAR(255) NOT NULL,
  expiration_date INT(11) UNSIGNED NOT NULL,
  repository_id INT(10) UNSIGNED NOT NULL,
  operation_name VARCHAR(16) NOT NULL,
  user_id INT(11) NOT NULL,
  INDEX idx_expiration_date (expiration_date)
);

CREATE TABLE plugin_gitlfs_file_max_size (
  size INT(11) UNSIGNED NOT NULL PRIMARY KEY
);

# 536870912 bytes = 512 Mb
INSERT INTO plugin_gitlfs_file_max_size (size) VALUES (536870912);