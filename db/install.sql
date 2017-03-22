##
## Sql Install Script
##

CREATE TABLE `plugin_botmattermost_agiledashboard_notification` (
    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    bot_id int(11) UNSIGNED NOT NULL ,
    project_id int(11) UNSIGNED NOT NULL UNIQUE ,
    send_time time NOT NULL
);

CREATE TABLE `plugin_botmattermost_agiledashboard_notification_channel` (
    notification_id int(11) NOT NULL ,
    channel_name VARCHAR(255) NOT NULL ,
    PRIMARY KEY(notification_id, channel_name)
);