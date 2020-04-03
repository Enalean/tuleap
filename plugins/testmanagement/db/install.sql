CREATE TABLE IF NOT EXISTS plugin_testmanagement(
    project_id INT(11) UNSIGNED NOT NULL PRIMARY KEY,
    campaign_tracker_id INT(11) NOT NULL,
    test_definition_tracker_id INT(11) NOT NULL,
    test_execution_tracker_id INT(11) NOT NULL,
    issue_tracker_id INT(11)
);

CREATE TABLE IF NOT EXISTS plugin_testmanagement_campaign(
    artifact_id INT(11) NOT NULL PRIMARY KEY,
    job_url VARCHAR(255) DEFAULT NULL,
    encrypted_job_token BLOB DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS plugin_testmanagement_execution(
    execution_artifact_id INT(11) NOT NULL PRIMARY KEY,
    definition_changeset_id INT(11) NOT NULL
);

CREATE TABLE IF NOT EXISTS plugin_testmanagement_changeset_value_stepdef(
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    changeset_value_id INT(11) NOT NULL,
    description TEXT,
    description_format VARCHAR(10) NOT NULL DEFAULT 'text',
    expected_results TEXT,
    expected_results_format VARCHAR(10) NOT NULL DEFAULT 'text',
    rank INT(11) UNSIGNED NOT NULL,
    INDEX cvid_idx(changeset_value_id, rank)
);

CREATE TABLE IF NOT EXISTS plugin_testmanagement_changeset_value_stepexec(
    changeset_value_id INT(11) NOT NULL,
    stepdef_id         INT(11) UNSIGNED NOT NULL,
    status             VARCHAR(20),
    PRIMARY KEY (changeset_value_id, stepdef_id)
);

-- Enable service for project 1 and 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_testmanagement:service_lbl_key' , 'plugin_testmanagement:service_desc_key' , 'plugin_testmanagement', '/plugins/testmanagement/?group_id=$group_id', 1 , 1 , 'system',  250 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_testmanagement:service_lbl_key' , 'plugin_testmanagement:service_desc_key' , 'plugin_testmanagement', '/plugins/testmanagement/?group_id=1', 1 , 1 , 'system',  250 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_testmanagement:service_lbl_key' , 'plugin_testmanagement:service_desc_key' , 'plugin_testmanagement', CONCAT('/plugins/testmanagement/?group_id=', group_id), 1 , 0 , 'system',  250
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'plugin_testmanagement');
