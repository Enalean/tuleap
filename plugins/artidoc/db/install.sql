DROP TABLE IF EXISTS plugin_artidoc_document;
CREATE TABLE plugin_artidoc_document
(
    item_id     INT(11) UNSIGNED NOT NULL,
    artifact_id INT(11)          NOT NULL,
    `rank`      INT(11) unsigned NOT NULL,
    PRIMARY KEY (item_id, artifact_id),
    INDEX idx_rank (item_id, `rank`)
) ENGINE = InnoDB;
