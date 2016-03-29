INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
       VALUES      ( 100, 'plugin_svn:service_lbl_key', 'plugin_svn:service_desc_key', 'plugin_svn', '/plugins/svn/?group_id=$group_id', 1, 0, 'system', 136 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
    SELECT DISTINCT group_id, 'plugin_svn:service_lbl_key', 'plugin_svn:service_desc_key', 'plugin_svn', CONCAT('/plugins/svn/?group_id=', group_id), 1, 0, 'system', 136
       FROM service
       WHERE group_id NOT IN (SELECT group_id
                              FROM service
                              WHERE short_name
                              LIKE 'plugin_svn');


CREATE TABLE plugin_svn_repositories(
  id  int(11) unsigned NOT NULL auto_increment,
  name  varchar(255) NOT NULL,
  project_id int(11) NOT NULL,
  accessfile_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY  (id),
  INDEX project_idx (project_id)
);

CREATE TABLE plugin_svn_hook_config(
  repository_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
  mandatory_reference BOOL DEFAULT false NOT NULL,
  commit_message_can_change BOOL DEFAULT false NOT NULL
);

CREATE TABLE plugin_svn_mailing_header(
  repository_id INT(11) UNSIGNED NOT NULL,
  header varchar(64) NOT NULL,
  PRIMARY KEY (repository_id)
);

CREATE TABLE plugin_svn_notification(
  repository_id INT(11) UNSIGNED NOT NULL,
  mailing_list text,
  svn_path varchar(255) DEFAULT '/',
  PRIMARY KEY (repository_id, svn_path)
);

CREATE TABLE plugin_svn_accessfile_history(
    id INT(11) UNSIGNED NOT NULL auto_increment PRIMARY KEY,
    version_number int(11) NOT NULL,
    repository_id int(11) NOT NULL,
    content text NOT NULL,
    version_date int(11) NOT NULL,
    INDEX repository_idx (repository_id)
);

CREATE TABLE plugin_svn_immutable_tag (
    repository_id INT(11) PRIMARY KEY,
    paths TEXT NOT NULL,
    whitelist TEXT NOT NULL
);
