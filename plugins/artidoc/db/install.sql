DROP TABLE IF EXISTS plugin_artidoc_section;
CREATE TABLE plugin_artidoc_section
(
    id          BINARY(16)       NOT NULL PRIMARY KEY,
    item_id     INT(11) UNSIGNED NOT NULL,
    INDEX idx (item_id)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_artidoc_section_version;
CREATE TABLE plugin_artidoc_section_version
(
    section_id  BINARY(16)       NOT NULL,
    artifact_id INT(11)          NULL,
    freetext_id BINARY(16)       NULL,
    `rank`      INT(11) unsigned NOT NULL,
    level       TINYINT unsigned NOT NULL,
    INDEX idx (section_id),
    INDEX idx_artifact (artifact_id)
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_artidoc_section_freetext;
CREATE TABLE plugin_artidoc_section_freetext
(
    id          BINARY(16)       NOT NULL PRIMARY KEY,
    title       TEXT             NOT NULL DEFAULT '',
    description MEDIUMTEXT       NOT NULL DEFAULT ''
) ENGINE = InnoDB;

DROP TABLE IF EXISTS plugin_artidoc_document_tracker;
CREATE TABLE plugin_artidoc_document_tracker
(
    item_id    INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    tracker_id INT(11)          NOT NULL
) ENGINE = InnoDB;


DROP TABLE IF EXISTS plugin_artidoc_section_upload;
CREATE TABLE plugin_artidoc_section_upload
(
    id              BINARY(16)   NOT NULL PRIMARY KEY,
    file_name       VARCHAR(255) NOT NULL DEFAULT '',
    file_size       BIGINT       NOT NULL DEFAULT 0,
    user_id         INT          NOT NULL,
    expiration_date INT UNSIGNED NULL,
    item_id         INT UNSIGNED NOT NULL,
    INDEX idx_date (expiration_date)
) ENGINE = InnoDB;
