## 
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_cloudstorage;
CREATE TABLE plugin_cloudstorage (
  id INT DEFAULT NULL DEFAULT 0,
  default_dropbox_id VARCHAR(255) DEFAULT NULL,
  default_drive_id VARCHAR(255) DEFAULT NULL
);

INSERT INTO plugin_cloudstorage(id, default_dropbox_id, default_drive_id) VALUES ( 0, '', '');

-- Enable service for project 1 and 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_cloudstorage:service_lbl_key' , 'plugin_cloudstorage:service_desc_key' , 'cloudstorage', '/plugins/cloudstorage/?group_id=$group_id', 1 , 1 , 'system',  230 );
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 1   , 'plugin_cloudstorage:service_lbl_key' , 'plugin_cloudstorage:service_desc_key' , 'cloudstorage', '/plugins/cloudstorage/?group_id=1', 1 , 0 , 'system',  230 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_cloudstorage:service_lbl_key' , 'plugin_cloudstorage:service_desc_key' , 'cloudstorage', CONCAT('/plugins/cloudstorage/?group_id=', group_id), 1 , 0 , 'system',  230
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'cloudstorage');

-- Create cloudstorage columns in docman plugin
ALTER TABLE plugin_docman_item ADD COLUMN cs_docid VARCHAR(255) DEFAULT NULL AFTER link_url;
ALTER TABLE plugin_docman_item ADD COLUMN cs_service VARCHAR(255) DEFAULT NULL AFTER cs_docid;
ALTER TABLE plugin_docman_item_deleted ADD COLUMN cs_docid VARCHAR(255) DEFAULT NULL AFTER link_url;
ALTER TABLE plugin_docman_item_deleted ADD COLUMN cs_service VARCHAR(255) DEFAULT NULL AFTER cs_docid;
