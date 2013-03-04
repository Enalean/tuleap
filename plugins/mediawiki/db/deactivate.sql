/*
* sample script to remove service from a project with short_name 'baah' and id 101
*/

DROP DATABASE `plugin_mediawiki_baah`;

USE codendi;
UPDATE codendi.service SET is_active = 0, is_used = 0 WHERE group_id = 101 AND short_name = 'plugin_mediawiki';