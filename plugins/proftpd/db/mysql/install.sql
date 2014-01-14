INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
       VALUES      ( 100, 'plugin_proftpd:service_lbl_key', 'plugin_proftpd:service_desc_key', 'plugin_proftpd', '/plugins/proftpd/?group_id=$group_id', 1, 0, 'system', 230 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
  SELECT DISTINCT group_id, 'plugin_proftpd:service_lbl_key', 'plugin_proftpd:service_desc_key', 'plugin_proftpd', CONCAT('/plugins/proftpd/?group_id=', group_id), 1, 0, 'system', 230
        FROM service
        WHERE group_id NOT IN (SELECT group_id
                               FROM service
                               WHERE short_name
                               LIKE 'plugin_proftpd');