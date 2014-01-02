CREATE TABLE IF NOT EXISTS plugin_git_remote_servers (
    id INT(11) UNSIGNED NOT NULL auto_increment,
    host VARCHAR(255) NOT NULL,
    http_port INT(11) UNSIGNED DEFAULT 80,
    ssh_port INT(11) UNSIGNED NOT NULL,
    login VARCHAR(255) NOT NULL,
    identity_file VARCHAR(255) NOT NULL,
    ssh_key TEXT NULL,
PRIMARY KEY (id));

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
  `repository_backend_type` varchar(16) DEFAULT 'gitshell',
  `repository_scope` varchar(1) NOT NULL,
  `repository_namespace` varchar(255),
  `remote_server_id` INT(11) UNSIGNED NULL,
  `remote_server_disconnect_date` INT(11) NULL,
  `remote_project_deleted` TINYINT DEFAULT '0',
  `remote_project_deleted_date` INT(11) NULL,
  PRIMARY KEY  (`repository_id`),
  KEY `project_id` (`project_id`)
);

CREATE TABLE IF NOT EXISTS `plugin_git_post_receive_mail` (
  `recipient_mail` varchar(255) NOT NULL,
  `repository_id` int(10) NOT NULL,
  KEY `repository_id` (`repository_id`)
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
   INDEX `idx_repository_user`(`repository_id`, `user_id`));

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
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_git:service_lbl_key', 'plugin_git:service_desc_key', 'plugin_git', '/plugins/git/?group_id=$group_id', 1, 0, 'system', 230 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
  SELECT DISTINCT group_id, 'plugin_git:service_lbl_key', 'plugin_git:service_desc_key', 'plugin_git', CONCAT('/plugins/git/?group_id=', group_id), 1, 0, 'system', 230
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'plugin_git');
        
INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
VALUES (30, 'git', 'plugin_git:reference_commit_desc_key', '/plugins/git/index.php/$group_id/view/$1/?a=commit&h=$2', 'S', 'plugin_git', 'git_commit');

INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT 30, group_id, 1 FROM groups WHERE group_id;

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
       ('PLUGIN_GIT_WPLUS', 4, 0);

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
