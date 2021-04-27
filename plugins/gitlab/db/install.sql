CREATE TABLE IF NOT EXISTS plugin_gitlab_repository (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    gitlab_repository_id INT(11) NOT NULL,
    gitlab_repository_url VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    last_push_date INT(11) NOT NULL,
    UNIQUE (gitlab_repository_id, gitlab_repository_url)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_project (
    id INT(11) NOT NULL,
    project_id INT(11) NOT NULL,
    PRIMARY KEY (id, project_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_gitlab_repository_webhook_secret (
    repository_id INT(11) NOT NULL PRIMARY KEY,
    webhook_secret BLOB NOT NULL,
    gitlab_webhook_id INT(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_gitlab_commit_info (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    repository_id INT(11) NOT NULL,
    commit_sha1 BINARY(20) NOT NULL,
    commit_date INT(11) NOT NULL,
    commit_title TEXT NOT NULL,
    commit_branch VARCHAR(255) NOT NULL,
    author_name TEXT NOT NULL,
    author_email TEXT NOT NULL,
    INDEX commit_id(repository_id, commit_sha1(10))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_gitlab_bot_api_token (
    repository_id INT(11) NOT NULL PRIMARY KEY,
    token BLOB NOT NULL,
    is_email_already_send_for_invalid_token BOOL NOT NULL DEFAULT FALSE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_gitlab_merge_request_info (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    repository_id INT(11) NOT NULL,
    merge_request_id INT(11) NOT NULL,
    title TEXT NOT NULL,
    description TEXT NOT NULL,
    state TEXT NOT NULL,
    created_at INT(11) NOT NULL,
    author_name TEXT DEFAULT NULL,
    author_email TEXT DEFAULT NULL,
    UNIQUE KEY merge_request_id(repository_id, merge_request_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS plugin_gitlab_tag_info (
    id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    repository_id INT(11) NOT NULL,
    commit_sha1 BINARY(20) NOT NULL,
    tag_name TEXT NOT NULL,
    tag_message TEXT NOT NULL,
    INDEX idx_tag(repository_id, tag_name(10))
) ENGINE=InnoDB;
