##
## Sql Install Script
##
CREATE TABLE plugin_teamforge_compat_table (
  source VARCHAR(100),
  project_id INT(11) NOT NULL,
  target VARCHAR(100),
  PRIMARY KEY (source)
);
