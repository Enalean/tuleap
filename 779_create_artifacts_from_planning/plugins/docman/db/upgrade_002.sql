--
-- Table structure for table 'artifact_field_value_list'
--
--
-- field_id        : reference to the field id in artifact_field
-- value_id        : the id of the value
-- label           : the text value
-- description     : An explanation of the value (not used a lot but...)
-- rank            : number telling at which place in the select box
--                   a value must appear
-- status          : A the value is active. It displays in select boxes
--                   H the value is hidden (not shown in select boxes but
--                   it is still here for old artifacts using it
--                   P the value is permanent. It means that it is active and
--                   it cannot be changed to hidden by the project even if 
--                   artifact field has a 'project' scope (very useful to force
--                   some commonly accepted values to appear in the select
--                   box. The 'None' values are good examples of that)
--
--

CREATE TABLE plugin_docman_field_value_list (
  value_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  rank int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  PRIMARY KEY  (value_id),
  KEY idx_fv_value_id (value_id),
  KEY idx_fv_status (status)
);

CREATE TABLE plugin_docman_field_value_list_field (
  field_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  PRIMARY KEY  (field_id, value_id)
);

ALTER TABLE plugin_docman_field_value ADD COLUMN valueInt INT(11) NULL AFTER item_id;

-- INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (1, 'DOS', 'Document Objective Specifications', 10);
-- INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (2, 'PQP', 'Project Quality Plan', 20);
-- INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (3, 'PAS', '', 20);
--INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (7, 'None', '', 1);

-- INSERT INTO plugin_docman_field_value_list_field VALUES(4 ,1);
-- INSERT INTO plugin_docman_field_value_list_field VALUES(4 ,2);
-- INSERT INTO plugin_docman_field_value_list_field VALUES(4 ,3);
--INSERT INTO plugin_docman_field_value_list_field VALUES(4, 7);

-- INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (4, 'Mat10', '', 10);
-- INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (5, 'Mat20', '', 20);
-- INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (6, 'Mat30', '', 20);
--INSERT INTO plugin_docman_field_value_list (value_id,name,description,rank) VALUES (8, 'None', '', 1);

-- INSERT INTO plugin_docman_field_value_list_field VALUES(5 ,4);
-- INSERT INTO plugin_docman_field_value_list_field VALUES(5 ,5);
-- INSERT INTO plugin_docman_field_value_list_field VALUES(5 ,6);
--INSERT INTO plugin_docman_field_value_list_field VALUES(5 ,8);