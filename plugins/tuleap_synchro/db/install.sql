DROP TABLE IF EXISTS plugin_tuleap_synchro_endpoint;
CREATE TABLE plugin_tuleap_synchro_endpoint (
  username_source text NOT NULL,
  password_source VARCHAR(255) NOT NULL,
  project_source VARCHAR(30) NOT NULL,
  tracker_source INT(11) NOT NULL,
  username_target text NOT NULL,
  project_target VARCHAR(30) NOT NULL,
  base_uri VARCHAR(100) NOT NULL,
  webhook VARCHAR(17) NOT NULL PRIMARY KEY
) ENGINE=InnoDB;