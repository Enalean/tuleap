DROP TABLE IF EXISTS plugin_label_widget;
CREATE TABLE plugin_label_widget (
  content_id INT(11) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY
);

DROP TABLE IF EXISTS plugin_label_widget_config;
CREATE TABLE plugin_label_widget_config (
    content_id INT(11) UNSIGNED NOT NULL,
    label_id INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (content_id, label_id)
);
