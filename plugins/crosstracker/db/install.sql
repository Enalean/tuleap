DROP TABLE IF EXISTS plugin_crosstracker_query;
CREATE TABLE plugin_crosstracker_query
(
    id          BINARY(16) NOT NULL PRIMARY KEY,
    widget_id   INT        NOT NULL,
    query       TEXT       NOT NULL,
    title       TEXT       NOT NULL,
    description TEXT       NOT NULL DEFAULT '',
    is_default  TINYINT    NOT NULL DEFAULT false
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_crosstracker_widget;
CREATE TABLE plugin_crosstracker_widget
(
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT
) ENGINE=InnoDB;
