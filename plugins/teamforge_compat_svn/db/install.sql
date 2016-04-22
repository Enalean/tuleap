CREATE TABLE plugin_teamforge_compat_svn (
  source VARCHAR(100),
  repository_id INT(11) UNSIGNED NOT NULL,
  revision_id INT(11) UNSIGNED NOT NULL,
  INDEX idx(source(10), repository_id)
);
