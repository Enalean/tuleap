##
## Sql Install Script for plugin_tracker_encryption_key
##
DROP TABLE IF EXISTS plugin_tracker_encryption_key;
CREATE TABLE plugin_tracker_encryption_key(
    key_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    key_content TEXT,
    tracker_id INT(11) UNSIGNED NOT NULL UNIQUE,
    INDEX tracker_id_idx(tracker_id)
) ENGINE=InnoDB;
