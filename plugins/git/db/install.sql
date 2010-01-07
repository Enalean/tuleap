DROP TABLE IF EXISTS plugin_git;


CREATE TABLE IF NOT EXISTS `plugin_git` (
  `repository_id` int(10) unsigned NOT NULL auto_increment,
  `repository_name` varchar(255) NOT NULL,
  `repository_description` text,
  `repository_path` varchar(255) NOT NULL,
  `repository_parent_id` int(11) default NULL,
  `project_id` int(11) NOT NULL default '0',
  `repository_creation_user_id` int(11) NOT NULL,
  `repository_creation_date` datetime NOT NULL,
  `repository_deletion_date` datetime NOT NULL,
  `repository_is_initialized` tinyint(4) NOT NULL default '0',
  `repository_access` varchar(255) NOT NULL default 'private',
  PRIMARY KEY  (`repository_id`),
  KEY `project_id` (`project_id`)
);

#Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
    SELECT group_id , 'plugin_git:service_lbl_key' , 'plugin_git:service_desc_key' , 'plugin_git', CONCAT('/plugins/git/?group_id=', group_id), 1 , 0 , 'system',  230
    FROM groups;
        
