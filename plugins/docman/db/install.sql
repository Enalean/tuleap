CREATE TABLE plugin_docman_item (
  item_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  parent_id INT(11) UNSIGNED NULL,
  group_id INT(11) UNSIGNED NULL,
  title TEXT NULL,
  description TEXT NULL,
  create_date INT(11) UNSIGNED NULL,
  update_date INT(11) UNSIGNED NULL,
  user_id INT(11) UNSIGNED NULL,
  rank INT(11) UNSIGNED NULL,
  item_type INT(11) UNSIGNED NULL,
  link_url INT(11) UNSIGNED NULL,
  wiki_page INT(11) UNSIGNED NULL,
  file_is_embedded INT(11) UNSIGNED NULL,
  PRIMARY KEY(item_id)
);

INSERT INTO service(service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (18 , 100 , 'plugin_docman:service_lbl_key' , 'plugin_docman:service_desc_key' , 'docman', '/plugins/docman/?group_id=$group_id', 1 , 0 , 'system',  95 );
