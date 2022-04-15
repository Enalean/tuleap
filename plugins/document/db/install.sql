CREATE TABLE plugin_document_search_criteria
(
    id         INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id int(11)          NOT NULL,
    name       varchar(255)     NOT NULL default '',
    INDEX project_idx(project_id)
) ENGINE = InnoDB;

CREATE TABLE plugin_document_search_columns
(
    id         INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id int(11)          NOT NULL,
    name       varchar(255)     NOT NULL default '',
    INDEX project_idx(project_id)
) ENGINE = InnoDB;
