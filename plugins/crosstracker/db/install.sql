DROP TABLE IF EXISTS plugin_crosstracker_query;
CREATE TABLE plugin_crosstracker_query
(
    id          INT  NOT NULL PRIMARY KEY AUTO_INCREMENT,
    query       TEXT NOT NULL,
    title       TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT ''
) ENGINE=InnoDB;
