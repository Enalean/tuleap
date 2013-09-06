DROP TABLE IF EXISTS plugin_testing_campaign;
DROP TABLE IF EXISTS plugin_testing_testexecution;
DROP TABLE IF EXISTS plugin_testing_testresult;

DELETE FROM service WHERE short_name='plugin_testing';