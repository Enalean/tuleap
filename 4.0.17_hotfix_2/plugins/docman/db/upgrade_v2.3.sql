ALTER TABLE plugin_docman_report ADD COLUMN description TEXT NULL AFTER advanced_search;
ALTER TABLE plugin_docman_report ADD COLUMN image INT(11) NULL AFTER description;
