CREATE TABLE IF NOT EXISTS plugin_tracker_functions_log
(
    id                     int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    status                 varchar(64)      NOT NULL,
    changeset_id           int(11)          NOT NULL,
    source_payload_json    text             NOT NULL,
    generated_payload_json text             NULL,
    error_message          text             NULL,
    execution_date         int(11)          NOT NULL,

    INDEX idx_changeset_id (changeset_id),
    INDEX ids_execution_date (execution_date)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_tracker_functions;
CREATE TABLE IF NOT EXISTS plugin_tracker_functions (
  tracker_id int(11) NOT NULL PRIMARY KEY,
  is_activated tinyint(1) NOT NULL
) ENGINE=InnoDB;
