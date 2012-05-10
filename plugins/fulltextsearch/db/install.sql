-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_fulltextsearch:service_lbl_key', 'plugin_fulltextsearch:service_desc_key', 'plugin_fulltextsearch', '/plugins/fulltextsearch/?group_id=$group_id', 1, 1, 'system', 152);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_fulltextsearch:service_lbl_key' , 'plugin_fulltextsearch:service_desc_key' , 'plugin_fulltextsearch', CONCAT('/plugins/fulltextsearch/?group_id=', group_id), 1 , 0 , 'system',  152
FROM service
WHERE group_id != 100;