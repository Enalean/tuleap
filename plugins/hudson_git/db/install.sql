CREATE TABLE IF NOT EXISTS plugin_hudson_git_server(
    `repository_id` int(10) unsigned NOT NULL,
    `jenkins_server_url` varchar(255) default '',
    PRIMARY KEY `repository_id` (`repository_id`)
);

CREATE TABLE plugin_hudson_git_job (
    id  int(11) unsigned NOT NULL auto_increment,
    repository_id int(10) NOT NULL,
    push_date int(11) NOT NULL,
    job_url text NOT NULL,
    PRIMARY KEY  (`id`)
);
