DROP TABLE IF EXISTS plugin_testing_campaign;
CREATE TABLE plugin_testing_campaign(
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  project_id int(11) NOT NULL,
  name text NOT NULL,
  product_version_id int(11) NULL
) ENGINE='InnoDB';

DROP TABLE IF EXISTS plugin_testing_testexecution;
CREATE TABLE plugin_testing_testexecution(
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  campaign_id int(11) unsigned NOT NULL,
  test_case_id int(11) unsigned NULL,
  test_version_id int(11) unsigned NULL,
  assigned_to int(11) NOT NULL
) ENGINE='InnoDB';

DROP TABLE IF EXISTS plugin_testing_testresult;
CREATE TABLE plugin_testing_testresult(
  id int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  execution_id int(11) unsigned NOT NULL,
  executed_by int(11) NOT NULL,
  executed_on int(11) NOT NULL,
  status tinyint(2) NOT NULL,
  message TEXT NOT NULL
) ENGINE='InnoDB';

DROP TABLE IF EXISTS plugin_testing_testexecution_defect;
CREATE TABLE plugin_testing_testexecution_defect(
      testexecution_id int(11) unsigned NOT NULL,
      defect_id int(11) NOT NULL,
      KEY defect_id (defect_id),
      KEY testexecution_id (testexecution_id),
      CONSTRAINT plugin_testing_testexecution_defects_ibfk_test
        FOREIGN KEY (testexecution_id)
        REFERENCES plugin_testing_testexecution (id)
        ON DELETE NO ACTION ON UPDATE NO ACTION,
      CONSTRAINT plugin_testing_testexecution_defects_ibfk_defect
        FOREIGN KEY (defect_id)
        REFERENCES tracker_artifact (id)
        ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE='InnoDB';

DROP TABLE IF EXISTS plugin_testing_requirement_testversion;
CREATE TABLE plugin_testing_requirement_testversion(
      requirement_id int(11) NOT NULL,
      testversion_id int(11) NOT NULL,
      KEY requirement_id (requirement_id),
      KEY testversion_id (testversion_id),
      CONSTRAINT plugin_testing_requirement_testversion_ibfk_test
        FOREIGN KEY (testversion_id)
        REFERENCES tracker_artifact (id)
        ON DELETE NO ACTION ON UPDATE NO ACTION,
      CONSTRAINT plugin_testing_requirement_testversion_ibfk_req
        FOREIGN KEY (requirement_id)
        REFERENCES tracker_artifact (id)
        ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE='InnoDB';

-- Enable service for project 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
       VALUES      ( 100, 'plugin_testing:descriptor_name', 'plugin_testing:descriptor_description', 'plugin_testing', '/plugins/testing/?group_id=$group_id', 1, 1, 'system', 240);


-- Create service for all projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank)
SELECT DISTINCT group_id , 'plugin_testing:descriptor_name' , 'plugin_testing:descriptor_description' , 'plugin_testing', CONCAT('/plugins/testing/?group_id=', group_id), 1 , 0 , 'system',  240
FROM service
WHERE group_id != 100;
