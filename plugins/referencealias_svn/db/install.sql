CREATE TABLE plugin_referencealias_svn (
  source VARCHAR(100) UNIQUE,
  repository_id INT(11) UNSIGNED NOT NULL,
  revision_id INT(11) UNSIGNED NOT NULL,
  INDEX idx(source(10), repository_id)
);
