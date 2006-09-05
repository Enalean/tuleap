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
  rank INT(11) UNSIGNED NULL,
  item_type INT(11) UNSIGNED NULL,
  link_url TEXT NULL,
  wiki_page TEXT NULL,
  file_is_embedded INT(11) UNSIGNED NULL,
  PRIMARY KEY(item_id)
);

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

CREATE TABLE plugin_docman_log (
  time      INT(11) UNSIGNED NOT NULL default '0',
  group_id  INT(11)          NOT NULL default '0',
  item_id   INT(11) UNSIGNED NOT NULL default '0',
  user_id   INT(11)          NOT NULL default '0',
  type      TINYINT(4)       NOT NULL default '0',
  old_value TEXT,
  new_value TEXT,
  KEY time (time),
  KEY item_id (item_id),
  KEY group_id (group_id)
);

CREATE TABLE plugin_docman_project_settings (
  group_id  INT(11)          NOT NULL default '0',
  view      VARCHAR(255),
  KEY group_id (group_id)
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

