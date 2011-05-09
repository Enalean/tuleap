DROP TABLE IF EXISTS plugin_codex_to_remedy;
CREATE TABLE plugin_codex_to_remedy (
    id INT(11) UNSIGNED NOT NULL auto_increment,
    user_id INT(11) UNSIGNED NULL,
    summary TEXT NOT NULL,
    create_date INT(11) UNSIGNED NULL,
    description TEXT NULL,
    type INT,
    severity INT,
    cc TEXT,
    PRIMARY KEY(id)
);