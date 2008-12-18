DROP TABLE IF EXISTS plugin_svntodimensions_log;
CREATE TABLE plugin_svntodimensions_log ( log_id INT( 11 ) NOT NULL auto_increment , group_id INT( 11 ) NOT NULL , date INT( 11 ) NOT NULL , tag VARCHAR( 255 ) NOT NULL , design_part VARCHAR( 255 ), user_id INT( 11 ) NOT NULL , state INT( 11 ) NOT NULL, error VARCHAR( 255 ) , PRIMARY KEY  (log_id));
DROP TABLE IF EXISTS plugin_svntodimensions_parameters;
CREATE TABLE plugin_svntodimensions_parameters ( group_id INT( 11 ) NOT NULL , product VARCHAR( 255 ) NOT NULL , dimensions_db VARCHAR( 255 ) NOT NULL , status INT( 11 ) NOT NULL );
-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100   , 'plugin_svntodimensions:service_lbl_key' , 'plugin_svntodimensions:service_desc_key' , 'svntodimensions', '/plugins/svntodimensions/?group_id=$group_id', 1 , 0 , 'system',  132 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_svntodimensions:service_lbl_key' , 'plugin_svntodimensions:service_desc_key' , 'svntodimensions', CONCAT('/plugins/svntodimensions/?group_id=', group_id), 1 , 0 , 'system',  132
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'svntodimensions');
