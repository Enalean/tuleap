CREATE TABLE plugin_project_certification_project_owner (
  project_id INT(11) NOT NULL,
  user_id INT(11) NOT NULL,
  PRIMARY KEY (project_id, user_id)
);
