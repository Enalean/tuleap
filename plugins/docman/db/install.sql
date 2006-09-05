DROP TABLE IF EXISTS plugin_docman_item;
CREATE TABLE plugin_docman_item (
  item_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id INT(11) UNSIGNED NULL,
  group_id INT(11) UNSIGNED NULL,
  title TEXT NULL,
  description TEXT NULL,
  create_date INT(11) UNSIGNED NULL,
  update_date INT(11) UNSIGNED NULL,
  delete_date INT(11) UNSIGNED NULL,
  user_id INT(11) UNSIGNED NULL,
  status TINYINT(4) DEFAULT 0 NOT NULL,
  obsolescence_date int(11) DEFAULT 0 NOT NULL,
  rank INT(11) DEFAULT 0 NOT NULL,
  item_type INT(11) UNSIGNED NULL,
  link_url TEXT NULL,
  wiki_page TEXT NULL,
  file_is_embedded INT(11) UNSIGNED NULL,
  PRIMARY KEY(item_id)
);

DROP TABLE IF EXISTS plugin_docman_version;
CREATE TABLE plugin_docman_version (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id INT(11) UNSIGNED NULL,
  number INT(11) UNSIGNED NULL,
  user_id INT(11) UNSIGNED NULL,
  label TEXT NULL,
  changelog TEXT NULL,
  date INT(11) UNSIGNED NULL,
  filename TEXT NULL,
  filesize INT(11) UNSIGNED NULL,
  filetype TEXT NULL,
  path TEXT NULL,
  PRIMARY KEY(id)
);

DROP TABLE IF EXISTS plugin_docman_log;
CREATE TABLE plugin_docman_log (
  time      INT(11) UNSIGNED NOT NULL default '0',
  group_id  INT(11)          NOT NULL default '0',
  item_id   INT(11) UNSIGNED NOT NULL default '0',
  user_id   INT(11)          NOT NULL default '0',
  type      TINYINT(4)       NOT NULL default '0',
  old_value TEXT,
  new_value TEXT,
  field TEXT DEFAULT NULL,
  KEY time (time),
  KEY item_id (item_id),
  KEY group_id (group_id)
);

DROP TABLE IF EXISTS plugin_docman_project_settings;
CREATE TABLE plugin_docman_project_settings (
  group_id  INT(11)          NOT NULL default '0',
  view      VARCHAR(255),
  use_obsolescence_date TINYINT(4) DEFAULT 0 NOT NULL,
  use_status TINYINT(4) DEFAULT 0 NOT NULL,
  KEY group_id (group_id)
);

--
-- Table structure for table 'plugin_docman_metadata'
--
--
-- field_id      : id of the metadata
-- group_id      : id of the group where the metadata is
-- name          : the name of the field (must be indentical to the
--                 column name in the artifact table
-- data_type     : type of the value of this field
--                 TEXT = 1 - DATE = 4 - USER = 5 - STRING = 6
--
-- label         : short name (used on the HTML form)
-- description   : longer description of this field
-- required      : 0 a project can decide not to use this artifact field
--                 1 all projects have to use this artifact field
-- empty_ok      : 0 this field must always be assigned a value
--                 1 empty value (null) is ok
-- special       : 0 process this field as usual
--                 1 this field require some special processing
-- default_value : default value for the metadata
-- use_it        : 0 metadata not used
--                 1 metadata used
--
--
DROP TABLE IF EXISTS plugin_docman_metadata;
CREATE TABLE plugin_docman_metadata (
  field_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL,
  name varchar(255) NOT NULL default '',
  data_type int(11) NOT NULL default '0',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  default_value text NOT NULL default '',
  use_it TINYINT(4)  NOT NULL default '0',
  PRIMARY KEY  (field_id,group_id),
  KEY idx_name (name),
  KEY idx_group_id (group_id)
);

--
-- Table structure for table 'plugin_docman_metadata_value'
--
-- Store values of metadata for an item. It may have multiple values for a
-- given item (should only happend with valueInt, because it correspond to
-- multiple values selection in a select box).
--
-- field_id    : id of the metadata (FK plugin_docman_metadata.field_id)
-- item_id     : id of the item (FK plugin_docman_item.item_id)
-- valueInt    : id of the listofelement value
--               (FK plugin_docman_metadata_love.value_id)
-- valueText   : A text value
-- valueDate   : A date value (stored as an int: date based on EPOCH)
-- valueString : A string value
--
--
DROP TABLE IF EXISTS plugin_docman_metadata_value;
CREATE TABLE plugin_docman_metadata_value (
  field_id int(11) NOT NULL,
  item_id int(11) NOT NULL,
  valueInt INT(11) NULL,
  valueText text,
  valueDate int(11),
  valueString text,
  KEY idx_field_id (field_id),
  KEY idx_artifact_id (item_id)
);

--
-- Table structure for table 'plugin_docman_metadata_love'
-- 
-- 'love' stands for ListOfValuesElement
--
-- value_id        : the id of the value
-- name            : the text value
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
CREATE TABLE plugin_docman_metadata_love (
  value_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  rank int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  PRIMARY KEY  (value_id),
  KEY idx_fv_value_id (value_id),
  KEY idx_fv_status (status)
);


--
-- Table structure for table 'plugin_docman_metadata_love_md'
--
-- 'love' stands for ListOfValuesElement and 'md' for MetaData
--
-- Make the link between 'plugin_docman_listofvalues' and
-- 'plugin_docman_metadata' tables
--
-- field_id        : the id of the field (FK plugin_docman_metadata.field_id)
-- value_id        : the id of the value (in plugin_docman_metadata_love.value_id)
--
--
CREATE TABLE plugin_docman_metadata_love_md (
  field_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  PRIMARY KEY  (field_id, value_id)
);

INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 100 , 'plugin_docman:service_lbl_key' , 'plugin_docman:service_desc_key' , 'docman', '/plugins/docman/?group_id=$group_id', 1 , 0 , 'system',  95 );

INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_READ', 1, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_READ', 2, 1);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_READ', 3, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_READ', 4, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_WRITE', 1, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_WRITE', 2, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_WRITE', 3, 1);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_WRITE', 4, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_MANAGE', 1, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_MANAGE', 2, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_MANAGE', 3, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_MANAGE', 4, 1);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_ADMIN', 1, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_ADMIN', 2, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_ADMIN', 3, 0);
INSERT INTO `permissions_values` VALUES ('PLUGIN_DOCMAN_ADMIN', 4, 1);

INSERT INTO plugin_docman_metadata_love(value_id, name, description, rank, status) VALUES (100, 'love_special_none_name_key', 'love_special_none_desc_key', 0, 'P');
