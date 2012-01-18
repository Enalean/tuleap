 ALTER TABLE  plugin_docman_approval DROP INDEX version_id;
 ALTER TABLE  plugin_docman_approval DROP INDEX item_wiki;
 ALTER TABLE  plugin_docman_approval DROP INDEX item_id;
 ALTER TABLE  plugin_docman_approval  ADD UNIQUE version_id (version_id);
 ALTER TABLE  plugin_docman_approval  ADD UNIQUE item_id (item_id, wiki_version_id);







