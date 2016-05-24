##
## Sql Install Script
##
CREATE TABLE `plugin_botmattermost_agiledashboard` (
    bot_id int(11) UNSIGNED NOT NULL ,
    project_id int(11) UNSIGNED NOT NULL ,
    start_time time NOT NULL ,
    duration time NOT NULL ,
    PRIMARY KEY (bot_id, project_id)
);
