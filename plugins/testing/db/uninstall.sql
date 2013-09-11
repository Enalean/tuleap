DROP TABLE IF EXISTS plugin_testing_testexecution_defect;
DROP TABLE IF EXISTS plugin_testing_requirement_testversion;
DROP TABLE IF EXISTS plugin_testing_campaign;
DROP TABLE IF EXISTS plugin_testing_testresult;
DROP TABLE IF EXISTS plugin_testing_testexecution;

DELETE FROM service WHERE short_name='plugin_testing';
