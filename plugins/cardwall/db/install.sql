DROP TABLE IF EXISTS plugin_cardwall_renderer;
CREATE TABLE plugin_cardwall_renderer(
    renderer_id INT(11) NOT NULL PRIMARY KEY,
    field_id INT(11) UNSIGNED NOT NULL
);

DROP TABLE IF EXISTS plugin_cardwall_on_top;
CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top(
    tracker_id int(11) NOT NULL PRIMARY KEY,
    use_freestyle_columns tinyint(4) default 0
);

DROP TABLE IF EXISTS plugin_cardwall_on_top_column;
CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top_column(
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tracker_id INT(11) NOT NULL,
    label VARCHAR(255) NOT NULL,
    bg_red TINYINT UNSIGNED NULL,
    bg_green TINYINT UNSIGNED NULL,
    bg_blue TINYINT UNSIGNED NULL,
    INDEX idx_tracker_id(tracker_id)
);

DROP TABLE IF EXISTS plugin_cardwall_on_top_column_mapping_field;
CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top_column_mapping_field(
    cardwall_tracker_id INT(11) NOT NULL,
    tracker_id INT(11) NOT NULL,
    field_id INT(11) NULL,
    PRIMARY KEY idx(cardwall_tracker_id, tracker_id)
);

DROP TABLE IF EXISTS plugin_cardwall_on_top_column_mapping_field_value;
CREATE TABLE IF NOT EXISTS plugin_cardwall_on_top_column_mapping_field_value(
    cardwall_tracker_id INT(11) NOT NULL,
    tracker_id INT(11) NOT NULL,
    field_id INT(11) NULL,
    value_id INT(11) NOT NULL,
    column_id INT(11) NOT NULL,
    PRIMARY KEY idx(cardwall_tracker_id, tracker_id, field_id, value_id)
);