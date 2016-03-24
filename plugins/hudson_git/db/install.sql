CREATE TABLE IF NOT EXISTS plugin_hudson_git_server(
    `repository_id` int(10) unsigned NOT NULL,
    `jenkins_server_url` varchar(255) default '',
    PRIMARY KEY `repository_id` (`repository_id`)
);
