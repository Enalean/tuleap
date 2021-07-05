CREATE TABLE `forge_upgrade_bucket` (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    script VARCHAR(255) NOT NULL default '',
    start_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    end_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    status TINYINT(4) NOT NULL default '0',
    PRIMARY KEY (id)
) Engine=InnoDb;

CREATE TABLE `forge_upgrade_log` (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket_id INT(11) UNSIGNED,
    timestamp DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    logger VARCHAR(64) NOT NULL default '',
    level VARCHAR(32) NOT NULL default '',
    message TEXT NOT NULL,
    thread VARCHAR(32) NOT NULL default '',
    file VARCHAR(255) NOT NULL default '',
    line INT(11) NOT NULL default 0,
    PRIMARY KEY (id),
    FOREIGN KEY (bucket_id) REFERENCES forge_upgrade_bucket(id) ON DELETE CASCADE
) Engine=InnoDb;
