##
## Sql Install Script
##
CREATE TABLE IF NOT EXISTS `plugin_botmattermost_git` (
    `repository_id` int(10) unsigned NOT NULL,
    `bot_id` int(11) unsigned NOT NULL,
    PRIMARY KEY (`repository_id`, `bot_id`)
);
