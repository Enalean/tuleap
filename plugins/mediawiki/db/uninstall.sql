USE codendi;

DELETE FROM codendi.plugin
    WHERE name = 'mediawiki';

DROP VIEW group_plugin;
DROP VIEW plugins;