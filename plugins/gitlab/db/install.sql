CREATE TABLE IF NOT EXISTS plugin_gitlab_repository (
    id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id INT(11) NOT NULL,
    gitlab_id INT(11) NOT NULL,
    name VARCHAR(255) NOT NULL,
    path VARCHAR(255) NOT NULL,
    description TEXT,
    full_url VARCHAR(255) NOT NULL,
    last_push_date INT(11) NOT NULL,
    INDEX idx_project_id(project_id)
) ENGINE=InnoDB;
