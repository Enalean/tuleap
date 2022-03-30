-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
VALUES      ( 100, 'plugin_baseline:service_lbl_key', 'plugin_baseline:service_desc_key', 'plugin_baseline', '/plugins/baseline/?group_id=$group_id', 1, 0, 'system', 145 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
SELECT DISTINCT group_id, 'plugin_baseline:service_lbl_key', 'plugin_baseline:service_desc_key', 'plugin_baseline', CONCAT('/plugins/baseline/?group_id=', group_id), 1, 0, 'system', 145
FROM service
WHERE group_id NOT IN (SELECT group_id
                       FROM service
                       WHERE short_name
                           LIKE 'plugin_baseline');

-- Baseline entity
CREATE TABLE IF NOT EXISTS plugin_baseline_baseline
(
	id int auto_increment primary key,
	name varchar(255) not null,
	artifact_id int not null,
	user_id int not null,
	snapshot_date int not null
);

-- Comparison entity
CREATE TABLE IF NOT EXISTS plugin_baseline_comparison
(
	id int auto_increment primary key,
	name varchar(255) null,
	comment varchar(255) null,
	base_baseline_id int not null,
	compared_to_baseline_id int not null,
	user_id int not null,
	creation_date int not null
);

-- Role Assignment entity
CREATE TABLE IF NOT EXISTS plugin_baseline_role_assignment
(
	id int auto_increment primary key,
	user_group_id int not null,
	role varchar(255) not null,
	project_id int not null
);

