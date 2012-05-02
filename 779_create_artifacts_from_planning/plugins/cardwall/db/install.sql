DROP TABLE IF EXISTS plugin_cardwall_renderer;
CREATE TABLE plugin_cardwall_renderer(
    renderer_id INT(11) NOT NULL PRIMARY KEY,
    field_id INT(11) UNSIGNED NOT NULL
);