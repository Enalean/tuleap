## 
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_statistics_user_session;
CREATE TABLE plugin_statistics_user_session (
    user_id INT UNSIGNED DEFAULT 0 NOT NULL,
    time    INT UNSIGNED DEFAULT 0 NOT NULL
);