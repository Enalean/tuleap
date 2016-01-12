INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
       VALUES      ( 100, 'plugin_svn:service_lbl_key', 'plugin_svn:service_desc_key', 'plugin_svn', '/plugins/svn/?group_id=$group_id', 1, 0, 'system', 136 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
    SELECT DISTINCT group_id, 'plugin_svn:service_lbl_key', 'plugin_svn:service_desc_key', 'plugin_svn', CONCAT('/plugins/svn/?group_id=', group_id), 1, 0, 'system', 136
       FROM service
       WHERE group_id NOT IN (SELECT group_id
                              FROM service
                              WHERE short_name
                              LIKE 'plugin_svn');


CREATE TABLE plugin_svn_repositories (
  id  int(11) unsigned NOT NULL auto_increment,
  name  varchar(255) NOT NULL,
  project_id int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  INDEX project_idx (project_id)
);
