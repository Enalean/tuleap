CREATE TABLE plugin_cvstodimensions_log ( group_id INT( 11 ) NOT NULL , date INT( 11 ) NOT NULL , tag VARCHAR( 255 ) NOT NULL , user_id INT( 11 ) NOT NULL , state INT( 11 ) NOT NULL, error VARCHAR( 255 ));
CREATE TABLE plugin_cvstodimensions_parameters ( group_id INT( 11 ) NOT NULL , product VARCHAR( 255 ) NOT NULL , dimensions_db VARCHAR( 255 ) NOT NULL , status INT( 11 ) NOT NULL );
CREATE TABLE plugin_cvstodimensions_modules ( group_id INT( 11 ) NOT NULL , module VARCHAR( 255 ) NOT NULL , design_part VARCHAR( 255 ) NOT NULL );
-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100   , 'plugin_cvstodimensions:service_lbl_key' , 'plugin_cvstodimensions:service_desc_key' , 'cvstodimensions', '/plugins/cvstodimensions/?group_id=$group_id', 1 , 0 , 'system',  131 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_cvstodimensions:service_lbl_key' , 'plugin_cvstodimensions:service_desc_key' , 'cvstodimensions', CONCAT('/plugins/cvstodimensions/?group_id=', group_id), 1 , 0 , 'system',  131
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'cvstodimensions');
