## 
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_statistics_user_session;
CREATE TABLE plugin_statistics_user_session (
    user_id INT UNSIGNED DEFAULT 0 NOT NULL,
    time    INT UNSIGNED DEFAULT 0 NOT NULL
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_statistics_diskusage_group;
CREATE TABLE plugin_statistics_diskusage_group (
    group_id INT(11) DEFAULT 0 NOT NULL,
    service VARCHAR(64) NOT NULL DEFAULT '',
    date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    INDEX idx_date (date, group_id, service(5)),
    INDEX idx_group_id_date (group_id, date)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_statistics_diskusage_user;
CREATE TABLE plugin_statistics_diskusage_user (
    user_id INT(11) DEFAULT 0 NOT NULL,
    service VARCHAR(255) NOT NULL DEFAULT '',
    date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    INDEX idx_date (date, user_id, service(5))
) ENGINE = InnoDB;	

DROP TABLE IF EXISTS plugin_statistics_diskusage_site;
CREATE TABLE plugin_statistics_diskusage_site (
    service VARCHAR(255) NOT NULL DEFAULT '',
    date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    size BIGINT UNSIGNED DEFAULT 0 NOT NULL,
    INDEX idx_date (date, service(5))
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_statistics_disk_quota_exception;
CREATE TABLE plugin_statistics_disk_quota_exception (
    group_id int(11) NOT NULL,
    requester_id int(11) NOT NULL default '0',
    requested_size int(11) NOT NULL,
    exception_motivation text,
    request_date int(11) unsigned NOT NULL default '0',
    PRIMARY KEY (group_id)
) ENGINE = InnoDB;
