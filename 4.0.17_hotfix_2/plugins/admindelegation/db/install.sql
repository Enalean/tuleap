DROP TABLE IF EXISTS plugin_admindelegation_service_user;
CREATE TABLE plugin_admindelegation_service_user (
    service_id INT UNSIGNED NOT NULL DEFAULT '0',
    user_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (user_id, service_id)
);

DROP TABLE IF EXISTS plugin_admindelegation_service_user_log;
CREATE TABLE plugin_admindelegation_service_user_log (
    service_id INT UNSIGNED NOT NULL DEFAULT '0',
    user_id INT(11) UNSIGNED NOT NULL DEFAULT '0',
    date DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
    action VARCHAR(255) NOT NULL DEFAULT ''
);
