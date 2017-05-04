##
## Sql Install Script
##
CREATE TABLE IF NOT EXISTS `plugin_botmattermost_git_notification` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `repository_id` int(10) unsigned NOT NULL UNIQUE ,
    `bot_id` int(11) unsigned NOT NULL
);

CREATE TABLE plugin_botmattermost_git_notification_channel (
    notification_id int(11) NOT NULL ,
    channel_name VARCHAR(255) NOT NULL ,
    PRIMARY KEY(notification_id, channel_name)
);