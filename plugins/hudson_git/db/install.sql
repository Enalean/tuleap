CREATE TABLE IF NOT EXISTS plugin_hudson_git_server(
    `repository_id` int(10) unsigned NOT NULL,
    `jenkins_server_url` varchar(255) default '',
    encrypted_token BLOB DEFAULT NULL,
    is_commit_reference_needed BOOL NOT NULL DEFAULT TRUE,
    PRIMARY KEY `repository_id` (`repository_id`)
);

CREATE TABLE plugin_hudson_git_job (
    id int(11) unsigned NOT NULL auto_increment,
    repository_id int(10) NOT NULL,
    push_date int(11) NOT NULL,
    PRIMARY KEY  (`id`)
);

CREATE TABLE plugin_hudson_git_job_polling_url (
   job_id  int(11) UNSIGNED NOT NULL PRIMARY KEY,
   job_url text NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_hudson_git_job_branch_source (
   job_id  int(11) UNSIGNED NOT NULL PRIMARY KEY,
   status_code INT(4) UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_hudson_git_project_server(
   id BINARY(16) NOT NULL PRIMARY KEY,
   api_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE,
   project_id int(11) NOT NULL,
   jenkins_server_url varchar(255) default '',
   encrypted_token BLOB DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_hudson_git_project_server_job (
   id int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
   project_server_id BINARY(16) NOT NULL,
   repository_id int(10) UNSIGNED NOT NULL,
   push_date int(11) UNSIGNED NOT NULL,
   INDEX idx_project_server_id(project_server_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_hudson_git_project_server_job_polling_url (
   job_id int(11) UNSIGNED NOT NULL PRIMARY KEY,
   job_url text NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_hudson_git_project_server_job_branch_source (
    job_id  int(11) UNSIGNED NOT NULL PRIMARY KEY,
    status_code INT(4) UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE plugin_hudson_git_trigger_hook_token(
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    verifier VARCHAR(255) NOT NULL,
    expiration_date INT(11) UNSIGNED NOT NULL
) ENGINE=InnoDB;
