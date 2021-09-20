##
## Sql Install Script
##
CREATE TABLE `plugin_botmattermost_bot` (
    id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
    name varchar(255) NOT NULL ,
    webhook_url varchar(255) NOT NULL ,
    avatar_url varchar(255) NOT NULL,
    project_id INT(11) DEFAULT NULL,
    index idx_project_id (project_id)
);
