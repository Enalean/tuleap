##
## Sql Uninstall Script
##
DROP TABLE IF EXISTS plugin_cloudstorage;

DELETE FROM service WHERE short_name='cloudstorage';

DELETE FROM plugin_docman_item WHERE cs_service = 'dropbox';
DELETE FROM plugin_docman_item WHERE cs_service = 'drive';
DELETE FROM plugin_docman_item_deleted WHERE cs_service = 'dropbox';
DELETE FROM plugin_docman_item_deleted WHERE cs_service = 'drive';

ALTER TABLE plugin_docman_item DROP COLUMN cs_docid;
ALTER TABLE plugin_docman_item DROP COLUMN cs_service;
ALTER TABLE plugin_docman_item_deleted DROP COLUMN cs_service;
ALTER TABLE plugin_docman_item_deleted DROP COLUMN cs_docid;
