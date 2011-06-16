DROP TABLE IF EXISTS plugin_request_help;
CREATE TABLE plugin_request_help (
    id INT(11) UNSIGNED NOT NULL auto_increment,
    user_id INT(11) UNSIGNED NULL,
    ticket_id  varchar(255) NOT NULL default '',
    summary TEXT NOT NULL,
    create_date INT(11) UNSIGNED NULL,
    description TEXT NULL,
    type INT,
    severity INT,
    cc TEXT,
    PRIMARY KEY(id)
);