##
## Sql Install Script
##
CREATE TABLE `plugin_botmattermost_bot` (
    id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
    name varchar(255) NOT NULL ,
    webhook_url varchar(255) NOT NULL ,
    avatar_url varchar(255) NOT NULL
);

CREATE TABLE `plugin_botmattermost_channel` (
    bot_id int(11) UNSIGNED NOT NULL ,
    name varchar(255) NOT NULL ,
    PRIMARY KEY (`bot_id`, `name`)
);
