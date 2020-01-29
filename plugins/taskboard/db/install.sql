DROP TABLE IF EXISTS plugin_taskboard_usage;
CREATE TABLE IF NOT EXISTS plugin_taskboard_usage
(
    project_id INT(11)     NOT NULL PRIMARY KEY,
    board_type VARCHAR(10) NOT NULL
);

INSERT INTO plugin_taskboard_usage (project_id, board_type)
SELECT group_id, 'taskboard'
FROM `groups`, tuleap_installed_version
WHERE tuleap_installed_version.version >= '11.10.99.159';
