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

CREATE TABLE IF NOT EXISTS tracker_changeset_value_encrypted (
    changeset_value_id INT(11) NOT NULL,
    value text,
    PRIMARY KEY(changeset_value_id)
) ENGINE=InnoDB;