DROP TABLE IF EXISTS plugin_docman_item_id;
CREATE TABLE plugin_docman_item_id (
  id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_docman_item;
CREATE TABLE plugin_docman_item (
  item_id INT(11) UNSIGNED NOT NULL,
  parent_id INT(11) UNSIGNED NULL,
  group_id INT(11) UNSIGNED NULL,
  title TEXT NOT NULL DEFAULT '',
  description TEXT NOT NULL DEFAULT '',
  create_date INT(11) UNSIGNED NULL,
  update_date INT(11) UNSIGNED NULL,
  delete_date INT(11) UNSIGNED NULL,
  user_id INT(11) UNSIGNED NULL,
  status TINYINT(4) DEFAULT 100 NOT NULL,
  obsolescence_date int(11) DEFAULT 0 NOT NULL,
  `rank` INT(11) DEFAULT 0 NOT NULL,
  item_type INT(11) UNSIGNED NULL,
  link_url TEXT NULL,
  wiki_page TEXT NULL,
  file_is_embedded INT(11) UNSIGNED NULL,
  PRIMARY KEY(item_id),
  KEY idx_group_id (group_id),
  KEY parent_id (parent_id),
  KEY `rank` (`rank`),
  KEY search (group_id, delete_date, obsolescence_date),
  KEY idx_create_date (create_date),
  KEY idx_delete_date (delete_date),
  FULLTEXT fltxt_title (title),
  FULLTEXT fltxt_description (description),
  FULLTEXT fltxt (title, description)
);

DROP TABLE IF EXISTS plugin_docman_item_deleted;
CREATE TABLE plugin_docman_item_deleted (
  item_id INT(11) UNSIGNED NOT NULL,
  parent_id INT(11) UNSIGNED NULL,
  group_id INT(11) UNSIGNED NULL,
  title TEXT NULL,
  description TEXT NULL,
  create_date INT(11) UNSIGNED NULL,
  update_date INT(11) UNSIGNED NULL,
  delete_date INT(11) UNSIGNED NULL,
  purge_date INT(11) UNSIGNED NULL,
  user_id INT(11) UNSIGNED NULL,
  status TINYINT(4) DEFAULT 100 NOT NULL,
  obsolescence_date int(11) DEFAULT 0 NOT NULL,
  `rank` INT(11) DEFAULT 0 NOT NULL,
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
  authoring_tool VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY(id),
  KEY item_id (item_id),
  FULLTEXT fltxt (label, changelog, filename),
  FULLTEXT fltxt_filename (filename)
);

DROP TABLE IF EXISTS plugin_docman_version_coauthor;
CREATE TABLE plugin_docman_version_coauthor (
  version_id INT(11) UNSIGNED NOT NULL,
  user_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY (version_id, user_id)
);

DROP TABLE IF EXISTS plugin_docman_link_version;
CREATE TABLE plugin_docman_link_version (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id INT(11) UNSIGNED NOT NULL,
  number INT(11) UNSIGNED NOT NULL,
  user_id INT(11) UNSIGNED NOT NULL,
  label TEXT NULL,
  changelog TEXT NULL,
  date INT(11) UNSIGNED NULL,
  link_url TEXT NULL,
  PRIMARY KEY(id),
  KEY item_id (item_id)
);


CREATE TABLE plugin_docman_version_deleted (
  id INT(11) UNSIGNED NOT NULL,
  item_id INT(11) UNSIGNED NULL,
  number INT(11) UNSIGNED NULL,
  user_id INT(11) UNSIGNED NULL,
  label TEXT NULL,
  changelog TEXT NULL,
  create_date INT(11) UNSIGNED NULL,
  delete_date INT(11) UNSIGNED NULL,
  purge_date INT(11) UNSIGNED NULL,
  filename TEXT NULL,
  filesize INT(11) UNSIGNED NULL,
  filetype TEXT NULL,
  path TEXT NULL,
  PRIMARY KEY(id),
  KEY item_id (item_id)
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
  filename_pattern VARCHAR(255) DEFAULT '',
  is_filename_pattern_enforced TINYINT(1) DEFAULT 0,
  forbid_writers_to_update TINYINT(1) DEFAULT 0,
  forbid_writers_to_delete TINYINT(1) DEFAULT 0,
  KEY group_id (group_id)
);

DROP TABLE IF EXISTS plugin_docman_tokens;
CREATE TABLE plugin_docman_tokens (
   user_id INT(11) NOT NULL,
   token CHAR(32) NOT NULL,
   url text NOT NULL default '',
   created_at DATETIME NOT NULL,
   PRIMARY KEY(user_id, token)
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
  mul_val_ok TINYINT(4)  NOT NULL default '0',
  special int(11) NOT NULL default '0',
  default_value text NOT NULL default '',
  use_it TINYINT(4)  NOT NULL default '0',
  PRIMARY KEY  (field_id),
  KEY idx_name (name (10)),
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
  KEY idx_field_item_id (field_id, item_id),
  FULLTEXT fltxt (valueText, valueString),
  FULLTEXT fltxt_txt (valueText),
  FULLTEXT fltxt_str (valueString)
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
DROP TABLE IF EXISTS plugin_docman_metadata_love;
CREATE TABLE plugin_docman_metadata_love (
  value_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  `rank` int(11) NOT NULL default '0',
  status char(1) NOT NULL default 'A',
  PRIMARY KEY  (value_id),
  KEY `rank` (`rank`),
  KEY name (name (10)),
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
DROP TABLE IF EXISTS plugin_docman_metadata_love_md;
CREATE TABLE plugin_docman_metadata_love_md (
  field_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  PRIMARY KEY  (field_id, value_id)
);

--
-- Table structure for table 'plugin_docman_approval'
--
-- table_id    Id of the table
-- item_id     Id of the item (FK plugin_docman_item (item_id))
-- version_id  Id of the item version (FK plugin_docman_version (id))
-- wiki_version_Id Id of the wiki page version (FK wiki_version(version))
-- link_version_Id Id of the link version (FK plugin_docman_link_version(id))
-- table_owner User who creates the table (FK user (user_id))
-- date        Table creation date
-- description A text that describe why the approval is required.
-- status      Table activation state: 0 - Disabled / 1 - Enabled / 2 - Closed
-- notification Type of notification: 0 - Disabled / 1 - Once at all / 2 - Sequential
-- auto_status  Does the table automaticaly change document status (0 - false / 1 true)
--
DROP TABLE IF EXISTS plugin_docman_approval;
CREATE TABLE plugin_docman_approval (
  table_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  item_id INT(11) UNSIGNED NULL DEFAULT NULL,
  version_id INT(11) UNSIGNED NULL DEFAULT NULL,
  wiki_version_id INT(11) UNSIGNED NULL DEFAULT NULL,
  link_version_id INT(11) UNSIGNED NULL DEFAULT NULL,
  table_owner INT(11) UNSIGNED NOT NULL,
  date INT(11) UNSIGNED NULL,
  description TEXT NULL,
  status TINYINT(4) DEFAULT 0 NOT NULL,
  notification TINYINT(4) DEFAULT 0 NOT NULL,
  notification_occurence INT(11) DEFAULT 0,
  auto_status TINYINT(4) DEFAULT 0 NOT NULL,
  might_be_corrupted BOOL DEFAULT FALSE,
  PRIMARY KEY(table_id),
  UNIQUE KEY version_id (version_id),
  UNIQUE KEY uniq_link_version_id (link_version_id),
  UNIQUE KEY item_id(item_id,wiki_version_id)
);

--
-- Table structure for table 'plugin_docman_approval_user'
--
-- table_id    Id of the table the reviewer belong to (FK plugin_docman_approval (table_id))
-- reviewer_id Id of user member of the table (FK user (user_id))
-- date        Date of the decision.
-- state       State of the review: 0 - Not Yet / 1 - Approved / 2 - Rejected
-- comment     A text to comment the state.
-- version     The version of the document on approval
--
DROP TABLE IF EXISTS plugin_docman_approval_user;
CREATE TABLE plugin_docman_approval_user (
  table_id INT(11) UNSIGNED NOT NULL,
  reviewer_id INT(11) UNSIGNED NOT NULL,
  `rank` INT(11) DEFAULT 0 NOT NULL,
  date INT(11) UNSIGNED NULL,
  state TINYINT(4) DEFAULT 0 NOT NULL,
  comment TEXT NULL,
  version INT(11) UNSIGNED NULL,
  PRIMARY KEY(table_id, reviewer_id),
  INDEX `rank` (`rank`),
  INDEX idx_reviewer (reviewer_id, table_id)
);

--
-- Table structure for table 'plugin_docman_report'
--
-- report_id
-- name             Name of the search (listed in 'Search' select box)
-- title            Title of the report (if set, replace the default
--                  Prjname - Project Doc)
-- group_id         (FK groups.group_id)
-- user_id          Owner of the report (FK user.user_id)
-- item_id          On witch item (folder) the report applies.
-- scope            Scope of the report ('I' individual, 'P' project)
-- is_default       Is the default report (not in use).
-- advanced_search  Is this search 'Advanced' (for dates and list of values).
-- description      Text that describe the report.
-- image            Add an image (FK plugin_docman_item.item_id)
DROP TABLE IF EXISTS plugin_docman_report;
CREATE TABLE plugin_docman_report (
  report_id       int(11) NOT NULL auto_increment,
  name            varchar(255) NULL,
  title           varchar(255) NULL,
  group_id        int(11) NOT NULL,
  user_id         int(11) NOT NULL DEFAULT 100,
  item_id         int(11) NULL,
  scope           char(1) NOT NULL default 'I',
  is_default      tinyint(1) NOT NULL default 0,
  advanced_search tinyint(1) NOT NULL default 0,
  description     text NULL,
  image           int(11)NULL,
  PRIMARY KEY (report_id),
  INDEX group_idx (group_id),
  INDEX user_idx (user_id)
);

--
-- Table structure for table 'plugin_docman_report_metadata'
--
DROP TABLE IF EXISTS plugin_docman_report_filter;
CREATE TABLE plugin_docman_report_filter (
  report_id     INT(11) NOT NULL,
  label         VARCHAR(255) NOT NULL,
  value_love    INT(11) NULL,
  value_string  VARCHAR(255) NULL,
  value_date1   VARCHAR(32) NULL,
  value_date2   VARCHAR(32) NULL,
  value_date_op tinyint(2) NULL,
  INDEX report_label_idx(report_id, label(10))
);

DROP TABLE IF EXISTS plugin_docman_widget_embedded;
CREATE TABLE plugin_docman_widget_embedded(
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    owner_id int(11) unsigned NOT NULL,
    owner_type varchar(1) NOT NULL,
    title varchar(255) NOT NULL,
    item_id INT(11) UNSIGNED NOT NULL,
    KEY (owner_id, owner_type)
);

--
-- Table structure for table 'plugin_docman_item_lock'
-- Host informations about locked items
--
DROP TABLE IF EXISTS plugin_docman_item_lock;
CREATE TABLE plugin_docman_item_lock (
  item_id INT(11) UNSIGNED NOT NULL DEFAULT 0,
  user_id INT(11) NOT NULL DEFAULT 0,
  lock_date INT(11) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (item_id)
);

DROP TABLE IF EXISTS plugin_docman_notifications;
CREATE TABLE plugin_docman_notifications (
    item_id int(11) NOT NULL default '0',
    user_id int(11) NOT NULL default '0',
    type varchar(100) NOT NULL default '',
    PRIMARY KEY (item_id, user_id, type)
);

DROP TABLE IF EXISTS plugin_docman_notification_ugroups;
CREATE TABLE plugin_docman_notification_ugroups (
    item_id   INT(11) NOT NULL default 0,
    ugroup_id INT(11) NOT NULL default 0,
    type varchar(100) NOT NULL default '',
    PRIMARY KEY (item_id, ugroup_id, type)
);

DROP TABLE IF EXISTS plugin_docman_new_document_upload;
CREATE TABLE plugin_docman_new_document_upload (
    item_id INT(11) UNSIGNED PRIMARY KEY REFERENCES plugin_docman_item_id(id),
    expiration_date INT(11) UNSIGNED NOT NULL,
    parent_id INT(11) UNSIGNED NOT NULL,
    title TEXT NULL,
    description TEXT NULL,
    user_id INT(11) UNSIGNED NOT NULL,
    filename TEXT NULL,
    filesize INT(11) UNSIGNED NULL,
    status TINYINT(4) DEFAULT 100 NOT NULL,
    obsolescence_date int(11) DEFAULT 0 NOT NULL,
    INDEX idx_parentid (parent_id),
    INDEX idx_expiration_date (expiration_date)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_docman_new_version_upload;
CREATE TABLE plugin_docman_new_version_upload(
    id INT(11) UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    expiration_date INT(11) UNSIGNED NOT NULL,
    item_id INT(11) UNSIGNED NOT NULL,
    version_title TEXT NULL,
    changelog TEXT NULL,
    user_id INT(11) UNSIGNED NOT NULL,
    filename TEXT NULL,
    filesize INT(11) UNSIGNED NULL,
    is_file_locked BOOL NOT NULL,
    approval_table_action VARCHAR(6) NULL,
    status TINYINT(4) DEFAULT 100 NOT NULL,
    obsolescence_date INT(11) DEFAULT 0 NOT NULL,
    title varchar(255) NULL,
    description TEXT NULL,
    INDEX idx_expiration_date (expiration_date),
    INDEX idx_item_id (item_id)
) ENGINE=InnoDB;

CREATE TABLE plugin_document_search_criteria
(
    id         INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id int(11)          NOT NULL,
    name       varchar(255)     NOT NULL default '',
    INDEX project_idx(project_id)
) ENGINE = InnoDB;

CREATE TABLE plugin_document_search_columns
(
    id         INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
    project_id int(11)          NOT NULL,
    name       varchar(255)     NOT NULL default '',
    INDEX project_idx(project_id)
) ENGINE = InnoDB;


INSERT INTO forgeconfig VALUES ('plugin_docman_max_number_of_files', 50);
-- 67108864 = 64MB
INSERT INTO forgeconfig VALUES ('plugin_docman_max_file_size', 67108864);

-- Enable service for project 1 and 100
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`) VALUES ( 100 , 'plugin_docman:service_lbl_key' , 'plugin_docman:service_desc_key' , 'docman', '/plugins/docman/?group_id=$group_id', 1 , 0 , 'system',  95 );

-- Create service for all other projects (but disabled)
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, `rank`)
SELECT DISTINCT group_id , 'plugin_docman:service_lbl_key' , 'plugin_docman:service_desc_key' , 'docman', CONCAT('/plugins/docman/?group_id=', group_id), 1 , 0 , 'system',  95
FROM service
WHERE group_id NOT IN (SELECT group_id
    FROM service
    WHERE short_name
    LIKE 'docman');


--
-- Create document references
--

-- First, remove existing reference to legacy docman.
-- It was almost never used, and since we keep the same keywords, we can't keep both
DELETE FROM reference WHERE id='10';
DELETE FROM reference WHERE id='11';
DELETE FROM reference WHERE id='17';
DELETE FROM reference WHERE id='18';

-- Create new references
INSERT INTO reference SET
    id='10',
    keyword='doc',
    description='reference_doc_desc_key',
    link='/plugins/docman/?group_id=$group_id&action=show&id=$1',
    scope='S',
    service_short_name='docman',
    nature='document';

INSERT INTO reference SET
    id='11',
    keyword='document',
    description='reference_doc_desc_key',
    link='/plugins/docman/?group_id=$group_id&action=show&id=$1',
    scope='S',
    service_short_name='docman',
    nature='document';

INSERT INTO reference SET
    id='17',
    keyword='folder',
    description='reference_doc_desc_key',
    link='/plugins/docman/?group_id=$group_id&action=show&id=$1',
    scope='S',
    service_short_name='docman',
    nature='document';

INSERT INTO reference SET
    id='18',
    keyword='dossier',
    description='reference_doc_desc_key',
    link='/plugins/docman/?group_id=$group_id&action=show&id=$1',
    scope='S',
    service_short_name='docman',
    nature='document';

-- Enable document references for project 1 and 100
DELETE FROM reference_group WHERE reference_id='10' AND group_id='100';
DELETE FROM reference_group WHERE reference_id='11' AND group_id='100';
DELETE FROM reference_group WHERE reference_id='17' AND group_id='100';
DELETE FROM reference_group WHERE reference_id='18' AND group_id='100';
DELETE FROM reference_group WHERE reference_id='10' AND group_id='1';
DELETE FROM reference_group WHERE reference_id='11' AND group_id='1';
DELETE FROM reference_group WHERE reference_id='17' AND group_id='1';
DELETE FROM reference_group WHERE reference_id='18' AND group_id='1';
INSERT INTO reference_group SET reference_id='10', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='11', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='17', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='18', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='10', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='11', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='17', group_id='1', is_active='1';
INSERT INTO reference_group SET reference_id='18', group_id='1', is_active='1';



INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_READ', 1, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_READ', 2, 1);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_READ', 3, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_READ', 4, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_WRITE', 1, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_WRITE', 2, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_WRITE', 3, 1);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_WRITE', 4, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_MANAGE', 1, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_MANAGE', 2, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_MANAGE', 3, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_MANAGE', 4, 1);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_ADMIN', 1, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_ADMIN', 2, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_ADMIN', 3, 0);
INSERT INTO permissions_values VALUES ('PLUGIN_DOCMAN_ADMIN', 4, 1);

INSERT INTO plugin_docman_metadata_love(value_id, name, description, `rank`, status) VALUES (100, 'love_special_none_name_key', 'love_special_none_desc_key', 0, 'P');

-- Instanciate docman in default template project
INSERT INTO plugin_docman_item_id VALUES (NULL);
INSERT INTO plugin_docman_item (item_id, parent_id, group_id, title, description, create_date, update_date, delete_date, user_id, status, obsolescence_date, `rank`, item_type, link_url, wiki_page, file_is_embedded)
VALUES (LAST_INSERT_ID(), 0, 100, 'roottitle_lbl_key', '', UNIX_TIMESTAMP(NOW()), UNIX_TIMESTAMP(NOW()), NULL, 101, 100, 0, 0, 1, NULL, NULL, NULL);

INSERT INTO  plugin_docman_project_settings (group_id, view, use_obsolescence_date, use_status)
VALUES (100, 'Tree', 0, 0);

INSERT INTO permissions(permission_type, ugroup_id, object_id)
SELECT 'PLUGIN_DOCMAN_READ', 2, item_id
FROM plugin_docman_item
WHERE group_id = 100;

INSERT INTO permissions(permission_type, ugroup_id, object_id)
SELECT 'PLUGIN_DOCMAN_WRITE', 3, item_id
FROM plugin_docman_item
WHERE group_id = 100;

INSERT INTO permissions(permission_type, ugroup_id, object_id)
SELECT 'PLUGIN_DOCMAN_MANAGE', 4, item_id
FROM plugin_docman_item
WHERE group_id = 100;

INSERT INTO permissions(permission_type, ugroup_id, object_id)
VALUES ('PLUGIN_DOCMAN_ADMIN', 4, 100);

