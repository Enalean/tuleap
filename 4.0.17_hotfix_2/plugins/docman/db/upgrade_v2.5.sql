--
-- The primary key of plugin_docman_metadata is only field_id. The previous
-- model was inherited from trackers field definition but doesn't apply here.

ALTER TABLE plugin_docman_metadata DROP PRIMARY KEY, ADD PRIMARY KEY  (field_id);

ALTER TABLE plugin_docman_report ADD COLUMN item_id int(11) NULL AFTER user_id;
