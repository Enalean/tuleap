--
-- Increase general perf of docman
--
ALTER TABLE plugin_docman_item ADD INDEX idx_group_id (group_id);
ALTER TABLE plugin_docman_version ADD INDEX idx_item_id (item_id);
