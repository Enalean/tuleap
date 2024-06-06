DROP TABLE IF EXISTS plugin_artidoc_document;
CREATE TABLE plugin_artidoc_document
(
    id          BINARY(16)       NOT NULL PRIMARY KEY,
    item_id     INT(11) UNSIGNED NOT NULL,
    artifact_id INT(11)          NOT NULL,
    `rank`      INT(11) unsigned NOT NULL,
    UNIQUE idx_uniq_artifact (item_id, artifact_id),
    INDEX idx_rank (item_id, `rank`)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_artidoc_document_tracker;
CREATE TABLE plugin_artidoc_document_tracker
(
    item_id    INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    tracker_id INT(11)          NOT NULL
) ENGINE = InnoDB;
