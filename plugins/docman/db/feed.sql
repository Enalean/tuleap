drop table plugin_docman_item;

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


INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (2, 0, 'Architecture', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (3, 0, 'Delivery', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (4, 2, 'Docman', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (5, 3, 'Codex 2.4', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (6, 3, 'Codex 2.6', 101, 1);

