DROP TABLE IF EXISTS plugin_salome_proxy;
CREATE TABLE plugin_salome_proxy (
user_id INT( 11 ) NOT NULL ,
proxy VARCHAR( 128 ) NOT NULL ,
proxy_user VARCHAR( 128 ) NOT NULL ,
proxy_password VARCHAR( 128 ) NOT NULL ,
active TINYINT(1) NOT NULL ,
PRIMARY KEY (user_id)
);

DROP TABLE IF EXISTS plugin_salome_configuration;
CREATE TABLE plugin_salome_configuration (
group_id INT( 11 ) NOT NULL ,
name varchar(255) NOT NULL ,
value TINYINT(1) NOT NULL ,
PRIMARY KEY (group_id, name)
);

DROP TABLE IF EXISTS plugin_salome_activatedplugins;
CREATE TABLE plugin_salome_activatedplugins (
group_id INT( 11 ) NOT NULL ,
name varchar(255) NOT NULL ,
PRIMARY KEY (group_id, name)
);


-- Enable service for project 1 and 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_salome:service_lbl_key' , 'plugin_salome:service_desc_key' , 'salome', '/plugins/salome/?group_id=$group_id', 1 , 1 , 'system',  200 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_salome:service_lbl_key' , 'plugin_salome:service_desc_key' , 'salome', '/plugins/salome/?group_id=1', 1 , 0 , 'system',  200 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_salome:service_lbl_key' , 'plugin_salome:service_desc_key' , 'salome', CONCAT('/plugins/salome/?group_id=', group_id), 1 , 0 , 'system',  200
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'salome');

-- Add default plugins used by salome
INSERT INTO plugin_salome_activatedplugins(group_id, name) SELECT group_id, 'beanshell' FROM groups;
INSERT INTO plugin_salome_activatedplugins(group_id, name) SELECT group_id, 'simpleJunit' FROM groups;
INSERT INTO plugin_salome_activatedplugins(group_id, name) SELECT group_id, 'codenditracker' FROM groups;
INSERT INTO plugin_salome_activatedplugins(group_id, name) SELECT group_id, 'requirements' FROM groups;

