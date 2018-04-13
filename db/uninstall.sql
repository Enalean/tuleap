DROP TABLE IF EXISTS plugin_testmanagement;
DROP TABLE IF EXISTS plugin_testmanagement_campaign;
DROP TABLE IF EXISTS plugin_testmanagement_execution;

DELETE FROM service WHERE short_name = 'plugin_testmanagement';
