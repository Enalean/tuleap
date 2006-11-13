DROP TABLE IF EXISTS plugin_docman_item;
DROP TABLE IF EXISTS plugin_docman_version;
DROP TABLE IF EXISTS plugin_docman_log;
DROP TABLE IF EXISTS plugin_docman_project_settings;
DROP TABLE IF EXISTS plugin_docman_metadata;
DROP TABLE IF EXISTS plugin_docman_metadata_value;
DROP TABLE IF EXISTS plugin_docman_metadata_love;
DROP TABLE IF EXISTS plugin_docman_metadata_love_md;

DELETE FROM service WHERE short_name='docman';

DELETE FROM permissions_values WHERE permission_type LIKE 'PLUGIN_DOCMAN_%';
            
DELETE FROM permissions WHERE permission_type LIKE 'PLUGIN_DOCMAN_%';

DELETE FROM user_preferences WHERE preference_name LIKE 'plugin_docman%';

