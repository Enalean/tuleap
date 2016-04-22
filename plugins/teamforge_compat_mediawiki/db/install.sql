##
## Sql Install Script
##
CREATE TABLE plugin_teamforge_compat_mediawiki_table (
  source VARCHAR(100) PRIMARY KEY,
  project_id INT(11) NOT NULL,
  target VARCHAR(100)
);
