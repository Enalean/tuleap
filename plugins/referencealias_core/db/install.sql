##
## Sql Install Script
##
CREATE TABLE plugin_referencealias_core_table (
  source VARCHAR(100),
  project_id INT(11) NOT NULL,
  target VARCHAR(100),
  PRIMARY KEY (source)
);
