DELETE FROM service WHERE short_name='plugin_proftpd';

DROP TABLE IF EXISTS plugin_proftpd_xferlog;
DROP VIEW IF EXISTS ftpgroups;
DROP VIEW IF EXISTS ftpusers;
