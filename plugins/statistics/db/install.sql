## 
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_statistics_user_session;
CREATE TABLE plugin_statistics_user_session (
    user_id INT UNSIGNED DEFAULT 0 NOT NULL,
    time    INT UNSIGNED DEFAULT 0 NOT NULL
) TYPE = InnoDB;

DROP TABLE IF EXISTS plugin_statistics_diskusage_group;
CREATE TABLE plugin_statistics_diskusage_group (
    group_id INT(11) DEFAULT 0 NOT NULL,
    service VARCHAR(64) NOT NULL DEFAULT '',
    date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    INDEX idx_date (date, group_id, service(5)),
    INDEX idx_group_id_date (group_id, date)
) TYPE = InnoDB;

DROP TABLE IF EXISTS plugin_statistics_diskusage_user;
CREATE TABLE plugin_statistics_diskusage_user (
    user_id INT(11) DEFAULT 0 NOT NULL,
    service VARCHAR(255) NOT NULL DEFAULT '',
    date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    INDEX idx_date (date, user_id, service(5))
) TYPE = InnoDB;	

DROP TABLE IF EXISTS plugin_statistics_diskusage_site;
CREATE TABLE plugin_statistics_diskusage_site (
    service VARCHAR(255) NOT NULL DEFAULT '',
    date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    INDEX idx_date (date, service(5))
) TYPE = InnoDB;