##
## Sql Install Script
##
CREATE TABLE plugin_teamforge_compat_table (
  project_id INT(11),
  source VARCHAR(100),
  target VARCHAR(100),
  PRIMARY KEY (project_id, source)
);
