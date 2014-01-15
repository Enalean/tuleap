-- Designed after `man xferlog`
CREATE TABLE IF NOT EXISTS  plugin_proftpd_xferlog (
    id  INTEGER UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id int(11) NOT NULL,
    group_id int(11) NOT NULL,
    time INTEGER UNSIGNED NOT NULL DEFAULT 0,
    transfer_time INTEGER UNSIGNED NOT NULL DEFAULT 0,
    remote_host VARCHAR(255) NOT NULL DEFAULT '',
    file_size INTEGER UNSIGNED NOT NULL DEFAULT 0,
    file_name MEDIUMTEXT NOT NULL,
    transfer_type CHAR(1) NOT NULL DEFAULT '',
    special_action_flag  CHAR(1) NOT NULL DEFAULT '',
    direction CHAR(1) NOT NULL DEFAULT '',
    access_mode CHAR(1) NOT NULL DEFAULT '',
    username VARCHAR(255) NOT NULL DEFAULT '',
    service_name VARCHAR(255) NOT NULL DEFAULT '',
    authentication_method TINYINT(1) NOT NULL DEFAULT 0,
    authenticated_user_id INTEGER UNSIGNED NOT NULL DEFAULT 0,
    completion_status CHAR(1) NOT NULL DEFAULT '',
    INDEX idx_user_id(user_id),
    INDEX idx_group_id(user_id)
);
