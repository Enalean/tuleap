-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
VALUES      ( 100, 'plugin_baseline:service_lbl_key', 'plugin_baseline:service_desc_key', 'plugin_baseline', '/plugins/baseline/?group_id=$group_id', 1, 0, 'project', 145 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id, 'plugin_baseline:service_lbl_key', 'plugin_baseline:service_desc_key', 'plugin_baseline', CONCAT('/plugins/baseline/?group_id=', group_id), 1, 0, 'project', 145
FROM service
WHERE group_id NOT IN (SELECT group_id
                       FROM service
                       WHERE short_name
                           LIKE 'plugin_baseline');
