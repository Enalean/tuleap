DROP DATABASE 'plugin_mediawiki_admin';
DROP DATABASE 'plugin_mediawiki_baah';

USE codendi;
DELETE FROM codendi.service
    WHERE short_name = 'plugin_mediawiki';

DELETE FROM codendi.plugin
    WHERE name = 'mediawiki';