DROP TABLE IF EXISTS plugin_taskboard_usage;
CREATE TABLE IF NOT EXISTS plugin_taskboard_usage
(
    project_id INT(11)     NOT NULL PRIMARY KEY,
    board_type VARCHAR(10) NOT NULL
);
