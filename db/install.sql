CREATE TABLE IF NOT EXISTS plugin_pullrequest_review (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(256) NOT NULL,
    description TEXT NOT NULL,
    repository_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    creation_date INT(11) NOT NULL,
    branch_src VARCHAR(255) NOT NULL,
    sha1_src CHAR(40) NOT NULL,
    repo_dest_id INT(11) NOT NULL,
    branch_dest VARCHAR(255) NOT NULL,
    sha1_dest CHAR(40) NOT NULL,
    status VARCHAR(1) NOT NULL DEFAULT 'R',
    merge_status INT(2) NOT NULL,
    INDEX idx_pr_user_id(user_id),
    INDEX idx_pr_repository_id(repository_id)
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_comments (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    post_date INT(11) NOT NULL,
    content TEXT,
    INDEX idx_pr_pull_request_id(pull_request_id)
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_inline_comments (
    id              INT(11) PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11) NOT NULL,
    user_id         INT(11) NOT NULL,
    post_date       INT(11) NOT NULL,
    file_path       TEXT    NOT NULL,
    unidiff_offset  INT(6)  NOT NULL,
    content         TEXT    NOT NULL,
    is_outdated     BOOL    NOT NULL DEFAULT false
);
CREATE TABLE IF NOT EXISTS plugin_pullrequest_timeline_event (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    post_date INT(11) NOT NULL,
    type INT(3) NOT NULL,
    INDEX idx_pr_pull_request_id(pull_request_id)
);
