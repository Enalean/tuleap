-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
       VALUES      ( 100, 'plugin_testing:descriptor_name', 'plugin_testing:descriptor_description', 'plugin_testing', '/plugins/testing/?group_id=$group_id', 1, 1, 'system', 240);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_testing:descriptor_name' , 'plugin_testing:descriptor_description' , 'plugin_testing', CONCAT('/plugins/testing/?group_id=', group_id), 1 , 0 , 'system',  240
FROM service
WHERE group_id != 100;