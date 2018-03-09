##
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_velocity_semantic_field;
CREATE TABLE plugin_velocity_semantic_field (
  tracker_id int(11) NOT NULL,
  field_id int(11) unsigned NOT NULL,
  PRIMARY KEY (tracker_id, field_id)
) ENGINE=InnoDB;