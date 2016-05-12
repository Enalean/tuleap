CREATE TABLE plugin_referencealias_git (
  source VARCHAR(100) NOT NULL UNIQUE,
  repository_id INT(10) UNSIGNED NOT NULL,
  sha1 BINARY(20) NOT NULL
);
