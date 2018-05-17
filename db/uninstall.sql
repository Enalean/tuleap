DROP TABLE IF EXISTS plugin_testmanagement;
DROP TABLE IF EXISTS plugin_testmanagement_campaign;
DROP TABLE IF EXISTS plugin_testmanagement_execution;
DROP TABLE IF EXISTS plugin_testmanagement_changeset_value_stepdef;
DROP TABLE IF EXISTS plugin_testmanagement_changeset_value_stepexec;

DELETE FROM service WHERE short_name = 'plugin_testmanagement';
