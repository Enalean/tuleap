CREATE TABLE plugin_bugzilla_reference (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    server VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    are_followup_private TINYINT(1),
    INDEX keyword_idx(keyword(5))
) ENGINE=InnoDB;