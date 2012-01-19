
 ALTER TABLE plugin_docman_approval_user ADD INDEX idx_review (reviewer_id, table_id);

 ALTER TABLE  plugin_docman_approval ADD INDEX  idx_owner (table_owner, table_id);






