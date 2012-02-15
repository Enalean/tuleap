

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_agiledashboard:service_lbl_key', 'plugin_agiledashboard:service_desc_key', 'plugin_agiledashboard', '/plugins/agiledashboard/?group_id=$group_id', 1, 1, 'system', 152);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_agiledashboard:service_lbl_key' , 'plugin_agiledashboard:service_desc_key' , 'plugin_agiledashboard', CONCAT('/plugins/agiledashboard/?group_id=', group_id), 1 , 0 , 'system',  152
FROM service
WHERE group_id != 100;