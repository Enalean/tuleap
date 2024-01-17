CREATE TABLE IF NOT EXISTS plugin_git_remote_servers (
    id INT(11) UNSIGNED NOT NULL auto_increment,
    host VARCHAR(255) NOT NULL,
    http_port INT(11) UNSIGNED DEFAULT 80,
    ssh_port INT(11) UNSIGNED NOT NULL,
    login VARCHAR(255) NOT NULL,
    identity_file VARCHAR(255) NOT NULL,
    ssh_key TEXT NULL,
    use_ssl boolean DEFAULT 0,
    gerrit_version VARCHAR(255) NOT NULL DEFAULT '2.5',
    http_password VARCHAR(255),
    replication_password TEXT,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS `plugin_git` (
  `repository_id` int(10) unsigned NOT NULL auto_increment,
  `repository_name` varchar(255) NOT NULL,
  `repository_description` text,
  `repository_path` varchar(255) NOT NULL,
  `repository_parent_id` int(11) default NULL,
  `project_id` int(11) NOT NULL default '0',
  `repository_creation_user_id` int(11) NOT NULL,
  `repository_creation_date` datetime NOT NULL,
  `repository_deletion_date` datetime NOT NULL,
  `repository_is_initialized` tinyint(4) NOT NULL default '0',
  `repository_access` varchar(255) NOT NULL default 'private',
  `repository_events_mailing_prefix` varchar(64) DEFAULT '[SCM]',
  `repository_backend_type` varchar(16) DEFAULT 'gitolite',
  `repository_scope` varchar(1) NOT NULL,
  `repository_namespace` varchar(255),
  `repository_backup_path` text NULL,
  `remote_server_id` INT(11) UNSIGNED NULL,
  `remote_server_disconnect_date` INT(11) NULL,
  `remote_project_deleted_date` INT(11) NULL,
  `remote_server_migration_status` ENUM("QUEUE", "DONE", "ERROR") NULL,
  `ci_token` TEXT NULL,
  `allow_artifact_closure` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY  (`repository_id`),
  INDEX idx_project_repository(project_id, repository_id),
  INDEX idx_repository_creation_date(repository_creation_date)
);

CREATE TABLE IF NOT EXISTS `plugin_git_post_receive_mail` (
  `recipient_mail` varchar(255) NOT NULL,
  `repository_id` int(10) NOT NULL,
  KEY `repository_id` (`repository_id`)
);

CREATE TABLE IF NOT EXISTS plugin_git_post_receive_notification_user (
    repository_id INT(10) UNSIGNED NOT NULL,
    user_id INT(11) NOT NULL,
    PRIMARY KEY (repository_id, user_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_post_receive_notification_ugroup (
    repository_id INT(10) UNSIGNED NOT NULL,
    ugroup_id INT(11) NOT NULL,
    PRIMARY KEY (repository_id, ugroup_id)
);

-- Create plugin_git_log table --
CREATE TABLE IF NOT EXISTS `plugin_git_log` (
  `repository_id` int(10) unsigned NOT NULL,
  `user_id` int(11) unsigned NULL,
  `push_date` int(11) DEFAULT 0,
  `commits_number` int,
  `refname` TEXT NULL,
  `operation_type` varchar(64) NULL,
  `refname_type` varchar(64) NULL,
   INDEX `idx_repository_user`(`repository_id`, `user_id`),
   INDEX `idx_push_date`(`push_date`),
   INDEX idx_repository_date(repository_id, push_date)
);

CREATE TABLE IF NOT EXISTS `plugin_git_ci` (
`job_id` INT(11) UNSIGNED NOT NULL,
`repository_id` INT(10) UNSIGNED NOT NULL,
PRIMARY KEY (`job_id`));

CREATE TABLE IF NOT EXISTS plugin_git_remote_ugroups (
    group_id int(11) NOT NULL,
    ugroup_id int(11) NOT NULL,
    remote_server_id INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (group_id, ugroup_id, remote_server_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_housekeeping(
    allow_git_gc TINYINT(1) NOT NULL
);

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
       VALUES      ( 100, 'plugin_git:service_lbl_key', 'plugin_git:service_desc_key', 'plugin_git', '/plugins/git/?group_id=$group_id', 1, 0, 'system', 230 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
  SELECT DISTINCT group_id, 'plugin_git:service_lbl_key', 'plugin_git:service_desc_key', 'plugin_git', CONCAT('/plugins/git/?group_id=', group_id), 1, 0, 'system', 230
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'plugin_git');

INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
VALUES (30, 'git', 'plugin_git:reference_commit_desc_key', '/plugins/git/index.php/$group_id/view/$1/?a=commit&h=$2', 'S', 'plugin_git', 'git_commit');

INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT 30, group_id, 1 FROM `groups` WHERE group_id;

INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
VALUES (33, 'git_tag', 'plugin_git:reference_tag_desc_key', '/plugins/git/index.php/$group_id/view/$1/?a=tag&h=$2', 'S', 'plugin_git', 'git_tag');

INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT 33, group_id, 1 FROM `groups` WHERE group_id;

INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
VALUES ('PLUGIN_GIT_READ', 2, 1),
       ('PLUGIN_GIT_READ', 3, 0),
       ('PLUGIN_GIT_READ', 4, 0),
       ('PLUGIN_GIT_READ', 1, 0),
       ('PLUGIN_GIT_WRITE', 2, 0),
       ('PLUGIN_GIT_WRITE', 3, 1),
       ('PLUGIN_GIT_WRITE', 4, 0),
       ('PLUGIN_GIT_WPLUS', 2, 0),
       ('PLUGIN_GIT_WPLUS', 3, 0),
       ('PLUGIN_GIT_WPLUS', 4, 0),
       ('PLUGIN_GIT_ADMIN', 1, 0),
       ('PLUGIN_GIT_ADMIN', 2, 0),
       ('PLUGIN_GIT_ADMIN', 3, 0),
       ('PLUGIN_GIT_ADMIN', 4, 1),
       ('PLUGIN_GIT_DEFAULT_READ', 2, 1),
       ('PLUGIN_GIT_DEFAULT_READ', 3, 0),
       ('PLUGIN_GIT_DEFAULT_READ', 4, 0),
       ('PLUGIN_GIT_DEFAULT_READ', 1, 0),
       ('PLUGIN_GIT_DEFAULT_WRITE', 2, 0),
       ('PLUGIN_GIT_DEFAULT_WRITE', 3, 1),
       ('PLUGIN_GIT_DEFAULT_WRITE', 4, 0),
       ('PLUGIN_GIT_DEFAULT_WPLUS', 2, 0),
       ('PLUGIN_GIT_DEFAULT_WPLUS', 3, 0),
       ('PLUGIN_GIT_DEFAULT_WPLUS', 4, 0);

INSERT INTO permissions(permission_type, ugroup_id, object_id)
VALUES ('PLUGIN_GIT_ADMIN', 4, 100);

-- Grant project_admins as PLUGIN_GIT_ADMIN for project 100

INSERT INTO permissions (permission_type, object_id, ugroup_id)
VALUES ('PLUGIN_GIT_ADMIN', 100, 4);

-- Enable git gc
INSERT INTO plugin_git_housekeeping VALUES (1);

-- Create table for Gerrit's refs/meta/config templates
CREATE TABLE IF NOT EXISTS plugin_git_gerrit_config_template (
    id INT(11) unsigned NOT NULL auto_increment,
    group_id INT(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    content TEXT,
    PRIMARY KEY (id),
    INDEX idx_gerrit_config_template_by_project (group_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_webhook_url (
    id int(11) unsigned PRIMARY KEY AUTO_INCREMENT,
    repository_id int(10) unsigned NOT NULL,
    url TEXT NOT NULL,
    INDEX idx_git_webhook_url_repository_id (repository_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_webhook_log (
    created_on int(11) NOT NULL,
    webhook_id int(11) unsigned NOT NULL,
    status TEXT NOT NULL,
    INDEX idx(webhook_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions_enabled (
    repository_id int(10) unsigned NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions (
    id int(11) UNSIGNED PRIMARY KEY auto_increment,
    repository_id int(10) unsigned NOT NULL,
    pattern VARCHAR(255) NOT NULL,
    INDEX idx_repository_fine_grained_permissions(repository_id, pattern(15)),
    UNIQUE unique_pattern (repository_id, pattern)
);

CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions_writers (
    permission_id int(11) UNSIGNED,
    ugroup_id int(11) NOT NULL,
    PRIMARY KEY (permission_id, ugroup_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_permissions_rewinders (
    permission_id int(11) UNSIGNED,
    ugroup_id int(11) NOT NULL,
    PRIMARY KEY (permission_id, ugroup_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions_enabled (
    project_id int(11) NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions (
    id int(11) UNSIGNED PRIMARY KEY auto_increment,
    project_id int(11) NOT NULL,
    pattern VARCHAR(255) NOT NULL,
    INDEX idx_default_fine_grained_permissions(project_id, pattern(15)),
    UNIQUE default_unique_pattern (project_id, pattern)
);

CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions_writers (
    permission_id int(11) UNSIGNED,
    ugroup_id int(11) NOT NULL,
    PRIMARY KEY (permission_id, ugroup_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_permissions_rewinders (
    permission_id int(11) UNSIGNED,
    ugroup_id int(11) NOT NULL,
    PRIMARY KEY (permission_id, ugroup_id)
);

CREATE TABLE plugin_git_full_history (
  time int(11) UNSIGNED NOT NULL,
  repository_id int(10) unsigned NOT NULL,
  user_id int(11) NOT NULL,
  INDEX time_idx(time, repository_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_full_history_checkpoint (
  last_timestamp int(11) UNSIGNED NOT NULL
);

CREATE TABLE IF NOT EXISTS plugin_git_log_read_daily (
  repository_id int(10) unsigned NOT NULL,
  user_id int(11) NOT NULL,
  day int(11) UNSIGNED NOT NULL,
  git_read int(11) UNSIGNED NOT NULL default 0,
  day_last_access_timestamp int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (repository_id, user_id, day),
  INDEX time_idx(day, repository_id),
  INDEX last_access_timestamp_idx(day_last_access_timestamp, repository_id)
);

CREATE TABLE plugin_git_file_logs_parse (
  file_name VARCHAR(255) NOT NULL,
  end_line BIGINT UNSIGNED,
  PRIMARY KEY (file_name)
);

CREATE TABLE IF NOT EXISTS plugin_git_fine_grained_regexp_enabled (
    enabled tinyint(1) UNSIGNED
);

CREATE TABLE IF NOT EXISTS plugin_git_repository_fine_grained_regexp_enabled (
    repository_id int(10) unsigned NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS plugin_git_default_fine_grained_regexp_enabled (
    project_id int(11) unsigned NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS plugin_git_restricted_gerrit_servers (
  gerrit_server_id INT(11) unsigned PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS plugin_git_restricted_gerrit_servers_allowed_projects (
  gerrit_server_id INT(11) unsigned NOT NULL,
  project_id INT(11) NOT NULL,
  PRIMARY KEY idx(gerrit_server_id, project_id)
);

CREATE TABLE IF NOT EXISTS plugin_git_global_parameters (
    name VARCHAR(255) NOT NULL PRIMARY KEY,
    value TEXT NOT NULL
);
INSERT INTO plugin_git_global_parameters(name, value) VALUES ('authorized_keys_managed', 'tuleap');

CREATE TABLE IF NOT EXISTS plugin_git_commit_status (
  id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  repository_id INT(10) UNSIGNED NOT NULL,
  commit_reference CHAR(40) NOT NULL,
  status INT(1) NOT NULL,
  date INT(11) NOT NULL,
  INDEX idx_repository_commit(repository_id, commit_reference)
);

CREATE TABLE IF NOT EXISTS plugin_git_big_object_authorized_project (
  project_id INT(11) UNSIGNED NOT NULL PRIMARY KEY
);

CREATE TABLE IF NOT EXISTS plugin_git_commit_details_cache (
    repository_id INT(10) UNSIGNED NOT NULL,
    commit_sha1 BINARY(20) NOT NULL,
    title TEXT NOT NULL,
    author_name TEXT NOT NULL,
    author_email TEXT NOT NULL,
    author_epoch INT(11) NOT NULL,
    committer_name TEXT NOT NULL,
    committer_email TEXT NOT NULL,
    committer_epoch INT(11) NOT NULL,
    first_branch TEXT NOT NULL,
    first_tag TEXT NOT NULL,
    INDEX idx(repository_id, commit_sha1)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_git_change_build_status_permissions (
    repository_id INT(10) UNSIGNED PRIMARY KEY,
    granted_user_groups_ids TEXT NOT NULL
) ENGINE=InnoDB;
