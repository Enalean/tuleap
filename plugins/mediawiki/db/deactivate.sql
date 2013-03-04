/*
* sample script to remove service from a project with short_name 'commetuveux' and id 104
*/

USE codendi;
UPDATE codendi.service SET is_active = 1, is_used = 0 WHERE group_id = 104 AND short_name = 'plugin_mediawiki';

DROP DATABASE `plugin_mediawiki_commetuveux`;