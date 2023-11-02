CREATE TABLE IF NOT EXISTS plugin_pullrequest_review (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(256) NOT NULL,
    description TEXT NOT NULL,
    description_format VARCHAR(10) NOT NULL,
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

CREATE TABLE IF NOT EXISTS plugin_pullrequest_git_reference (
    pr_id INT(11) PRIMARY KEY,
    reference_id INT(11) NOT NULL,
    repository_dest_id INT(11) NOT NULL,
    status INT(11) NOT NULL,
    UNIQUE (repository_dest_id, reference_id)
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_comments
(
    id              INT(11) PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11) NOT NULL,
    user_id         INT(11) NOT NULL,
    post_date       INT(11) NOT NULL,
    last_edition_date INT(11) DEFAULT NULL,
    content         TEXT,
    parent_id       INT(11) NOT NULL,
    color           VARCHAR(50) DEFAULT '',
    format VARCHAR(10) NOT NULL DEFAULT 'text',
    INDEX idx_pr_pull_request_id (pull_request_id)
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_inline_comments
(
    id              INT(11) PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11)     NOT NULL,
    user_id         INT(11)     NOT NULL,
    post_date       INT(11)     NOT NULL,
    last_edition_date INT(11) DEFAULT NULL,
    file_path       TEXT        NOT NULL,
    unidiff_offset  INT(6)      NOT NULL,
    content         TEXT        NOT NULL,
    is_outdated     BOOL        NOT NULL DEFAULT false,
    position        VARCHAR(10) NOT NULL DEFAULT 'right',
    parent_id       INT(11)     NOT NULL,
    format VARCHAR(10) NOT NULL DEFAULT 'text',
    color VARCHAR(50) DEFAULT ''
);
CREATE TABLE IF NOT EXISTS plugin_pullrequest_timeline_event (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    post_date INT(11) NOT NULL,
    type INT(3) NOT NULL,
    INDEX idx_pr_pull_request_id(pull_request_id)
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_label (
    pull_request_id INT(11) NOT NULL,
    label_id INT(11) UNSIGNED NOT NULL,
    PRIMARY KEY (label_id, pull_request_id)
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_merge_setting (
    repository_id INT(10) UNSIGNED NOT NULL PRIMARY KEY,
    merge_commit_allowed BOOLEAN NOT NULL
);

CREATE TABLE IF NOT EXISTS plugin_pullrequest_template_merge_setting (
    project_id INT(11) NOT NULL PRIMARY KEY,
    merge_commit_allowed BOOLEAN NOT NULL
);

CREATE TABLE plugin_pullrequest_reviewer_change (
    change_id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    pull_request_id INT(11) NOT NULL,
    user_id INT(11) UNSIGNED NOT NULL,
    change_date INT(11) NOT NULL,
    INDEX idx_pr_pull_request_id(pull_request_id)
);

CREATE TABLE plugin_pullrequest_reviewer_change_user (
   change_id INT(11) UNSIGNED NOT NULL,
   user_id INT(11) UNSIGNED NOT NULL,
   is_removal BOOLEAN NOT NULL,
   PRIMARY KEY (change_id, user_id)
);

INSERT INTO reference (id, keyword, description, link, scope, service_short_name, nature)
VALUES (31, 'pr', 'plugin_pullrequest:reference_pullrequest_desc_key', '/plugins/git/?action=pull-requests&repo_id=$repo_id&group_id=$group_id#/pull-requests/$1/overview', 'S', 'plugin_pullrequest', 'pullrequest'),
(32, 'pullrequest', 'plugin_pullrequest:reference_pullrequest_desc_key', '/plugins/git/?action=pull-requests&repo_id=$repo_id&group_id=$group_id#/pull-requests/$1/overview', 'S', 'plugin_pullrequest', 'pullrequest');

INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT 31, group_id, 1 FROM `groups` WHERE group_id;

INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT 32, group_id, 1 FROM `groups` WHERE group_id;
