ALTER TABLE plugin ADD COLUMN prj_restricted TINYINT(4) NOT NULL DEFAULT 0 AFTER available;

ALTER TABLE project_plugin ADD UNIQUE prj_plugin (project_id, plugin_id);
ALTER TABLE project_plugin ADD INDEX project_id_idx (project_id);
ALTER TABLE project_plugin ADD INDEX plugin_id_idx (plugin_id);
