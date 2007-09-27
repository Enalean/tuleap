ALTER TABLE plugin_docman_metadata_value DROP INDEX idx_field_id;
ALTER TABLE plugin_docman_metadata_value DROP INDEX idx_artifact_id;

ALTER TABLE plugin_docman_metadata_value ADD INDEX idx_field_item_id (field_id, item_id);

ALTER TABLE plugin_docman_item ADD INDEX parent_id (parent_id);
ALTER TABLE plugin_docman_item ADD INDEX rank (rank);

ALTER TABLE plugin_docman_metadata DROP INDEX idx_name;
ALTER TABLE plugin_docman_metadata ADD INDEX idx_name (name (10));

ALTER TABLE plugin_docman_metadata_love DROP INDEX idx_fv_value_id;
ALTER TABLE plugin_docman_metadata_love ADD INDEX rank (rank);
ALTER TABLE plugin_docman_metadata_love ADD INDEX name (name (10));
