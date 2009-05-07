DROP TABLE IF EXISTS plugin_hudson_job;
CREATE TABLE plugin_hudson_job (
  job_id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  group_id int(11) NOT NULL ,
  job_url varchar(255) NOT NULL ,
  name varchar(128) NOT NULL ,
  use_svn_trigger tinyint(4) NOT NULL default 0 ,
  use_cvs_trigger tinyint(4) NOT NULL default 0 ,
  token varchar(128) NOT NULL
);

DROP TABLE IF EXISTS plugin_hudson_widget;
CREATE TABLE plugin_hudson_widget (
  id int(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  widget_name varchar(64) NOT NULL ,
  owner_id int(11) UNSIGNED NOT NULL ,
  owner_type varchar(1) NOT NULL ,
  job_id int(11) NOT NULL
);

-- Enable service for project 1 and 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', '/plugins/hudson/?group_id=$group_id', 1 , 1 , 'system',  220 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', '/plugins/hudson/?group_id=1', 1 , 0 , 'system',  220 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_hudson:service_lbl_key' , 'plugin_hudson:service_desc_key' , 'hudson', CONCAT('/plugins/hudson/?group_id=', group_id), 1 , 0 , 'system',  220
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'hudson');

-- Create references and add them into every project
INSERT INTO reference SET 
    keyword='job', 
    description='plugin_hudson:reference_job_desc_key', 
    link='/plugins/hudson/?group_id=$group_id&action=view_job&job=$1', 
    scope='S', 
    service_short_name='hudson',
    nature='hudson_job';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups;
    
INSERT INTO reference SET 
    keyword='build', 
    description='plugin_hudson:reference_build_desc_key', 
    link='/plugins/hudson/?group_id=$group_id&action=view_build&job=$1&build=$2', 
    scope='S', 
    service_short_name='hudson',
    nature='hudson_build';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups;

INSERT INTO reference SET 
    keyword='build', 
    description='plugin_hudson:reference_build_desc_key', 
    link='/plugins/hudson/?group_id=$group_id&action=view_build&build=$1', 
    scope='S', 
    service_short_name='hudson',
    nature='hudson_build';
INSERT INTO reference_group (reference_id, group_id, is_active)
SELECT last_insert_id, group_id, 1
FROM (SELECT LAST_INSERT_ID() as last_insert_id) AS R, groups;    