ALTER TABLE plugin_docman_project_settings ADD COLUMN use_obsolescence_date TINYINT(4) DEFAULT 0 NOT NULL AFTER view;
ALTER TABLE plugin_docman_project_settings ADD COLUMN use_status TINYINT(4) DEFAULT 0 NOT NULL AFTER use_obsolescence_date;

ALTER TABLE plugin_docman_log ADD COLUMN field TEXT DEFAULT NULL AFTER new_value;

ALTER TABLE plugin_docman_item ADD COLUMN status TINYINT(4) DEFAULT 0 NOT NULL AFTER user_id;
ALTER TABLE plugin_docman_item ADD COLUMN obsolescence_date int(11) DEFAULT 0 NOT NULL AFTER status;

--
-- Table structure for table 'artifact_field'
--
-- field_name  : the name of the field (must be indentical to the
--               column name in the artifact table
-- data_type   : type of the value of this field
--               TEXT = 1 - DATE = 4 - USER = 5 - STRING = 6
--
-- label       : short name (used on the HTML form)
-- description : longer description of this field
-- scope       : S if predefined values are for the entire Codex,
--               P if values can be re-defined at the project level
-- required    : 0 a project can decide not to use this artifact field
--               1 all projects have to use this artifact field
-- empty_ok    : 0 this field must always be assigned a value
--               1 empty value (null) is ok
-- keep_history: 0 do not keep old field values in the artifact_history table
--               1 yes keep the old values in the history table
-- special     : 0 process this field as usual
--               1 this field require some special processing
--
CREATE TABLE plugin_docman_field (
  field_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL,
  name varchar(255) NOT NULL default '',
  data_type int(11) NOT NULL default '0',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
--  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  default_value text NOT NULL default '',
  use_it TINYINT(4)  NOT NULL default '0',
  PRIMARY KEY  (field_id,group_id),
  KEY idx_name (name),
  KEY idx_group_id (group_id)
);


CREATE TABLE plugin_docman_field_value (
  field_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
--  valueInt int(11),
  valueText text,
--  valueFloat float(10,4),
  valueDate int(11),
  valueString text,
  KEY idx_field_id (field_id),
  KEY idx_artifact_id (item_id)
);

--INSERT INTO plugin_docman_field (field_id, group_id, name, data_type, label, description, required, empty_ok, special, default_value, use_it) VALUES (1, 101, 'Comment / Keywords', 1, 'field_1', 'You can comment your document here.', 0, 1, 0, '', 1);
