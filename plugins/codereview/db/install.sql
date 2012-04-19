-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) 
       VALUES      ( 100, 'plugin_codereview:service_lbl_key', 'plugin_codereview:service_desc_key', 'plugin_codereview', '/plugins/codereview/?group_id=$group_id', 1, 1, 'system', 250);

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
  SELECT DISTINCT group_id, 'plugin_codereview:service_lbl_key', 'plugin_codereview:service_desc_key', 'plugin_codereview', CONCAT('/plugins/codereview/?group_id=', group_id), 1, 0, 'system', 250
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'plugin_codereview');
