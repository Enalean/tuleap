CREATE TABLE plugin_bugzilla_reference (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(255) NOT NULL,
    server VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL,
    encrypted_api_key BLOB NOT NULL,
    has_api_key_always_been_encrypted TINYINT(1) NOT NULL DEFAULT 1,
    are_followup_private TINYINT(1),
    rest_url VARCHAR(255),
    INDEX keyword_idx(keyword(5))
) ENGINE=InnoDB;