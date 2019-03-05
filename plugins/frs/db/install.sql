CREATE TABLE IF NOT EXISTS plugin_frs_release_artifact(
    release_id int(11) PRIMARY KEY,
    artifact_id int(11)
);

CREATE TABLE plugin_frs_file_upload (
    id int(11) PRIMARY KEY auto_increment,
    expiration_date int(11) UNSIGNED,
    release_id int(11) NOT NULL default '0',
    name text,
    file_size bigint NOT NULL default '0',
    user_id int(11),
    KEY idx_expiration_date(expiration_date),
    KEY idx_releaseid(release_id)
);
