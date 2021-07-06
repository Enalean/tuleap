CREATE TABLE `forge_upgrade_bucket` (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    script VARCHAR(255) NOT NULL default '',
    start_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    end_date DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    status TINYINT(4) NOT NULL default '0',
    PRIMARY KEY (id)
) Engine=InnoDb;
