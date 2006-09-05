
INSERT INTO service(group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ( 101 , 'plugin_docman:service_lbl_key' , 'plugin_docman:service_desc_key' , 'docman', '/plugins/docman/?group_id=101', 1 , 1 , 'system',  95 );

INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (2, 0, 'Architecture', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (3, 0, 'Delivery', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (4, 2, 'Docman', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (5, 3, 'Codex 2.4', 101, 1);
INSERT INTO plugin_docman_item(item_id, parent_id, title, group_id, item_type) VALUES (6, 3, 'Codex 2.6', 101, 1);

