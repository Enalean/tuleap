INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
       VALUES      ( 100, 'plugin_svn:service_lbl_key', 'plugin_svn:service_desc_key', 'plugin_svn', '/plugins/svn/?group_id=$group_id', 1, 0, 'system', 136 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
    SELECT DISTINCT group_id, 'plugin_svn:service_lbl_key', 'plugin_svn:service_desc_key', 'plugin_svn', CONCAT('/plugins/svn/?group_id=', group_id), 1, 0, 'system', 136
       FROM service
       WHERE group_id NOT IN (SELECT group_id
                              FROM service
                              WHERE short_name
                              LIKE 'plugin_svn');


CREATE TABLE plugin_svn_repositories(
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL,
  project_id int(11) NOT NULL,
  is_core BOOL NOT NULL DEFAULT 0,
  has_default_permissions BOOL NOT NULL DEFAULT 1,
  accessfile_id INT(11) UNSIGNED NOT NULL,
  repository_deletion_date INT(11) NULL,
  backup_path text NULL,
  PRIMARY KEY (id),
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
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    repository_id INT(11) UNSIGNED NOT NULL,
    mailing_list text,
    svn_path varchar(255) DEFAULT '/',
    INDEX repo_svn_idx (repository_id, svn_path)
);

CREATE TABLE plugin_svn_notification_users(
    notification_id INT(11) UNSIGNED NOT NULL,
    user_id INT(11) NOT NULL,
    PRIMARY KEY (notification_id, user_id)
);

CREATE TABLE plugin_svn_notification_ugroups(
    notification_id INT(11) UNSIGNED NOT NULL,
    ugroup_id INT(11) NOT NULL,
    PRIMARY KEY (notification_id, ugroup_id)
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

INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
VALUES ('PLUGIN_SVN_ADMIN', 4, 1);

CREATE TABLE plugin_svn_full_history (
  repository_id int(11) unsigned NOT NULL,
  user_id int(11) NOT NULL,
  day int(11) NOT NULL,
  svn_read_operations int(11) NOT NULL default 0,
  svn_write_operations int(11) NOT NULL default 0,
  svn_browse_operations int(11) NOT NULL default 0,
  PRIMARY KEY (repository_id, user_id, day)
);

CREATE TABLE plugin_svn_last_access (
  repository_id INT(11) UNSIGNED PRIMARY KEY,
  commit_date INT(11) UNSIGNED NOT NULL
);

INSERT INTO forgeconfig(name, value) VALUES ('plugin_svn_file_size_limit', '50');
