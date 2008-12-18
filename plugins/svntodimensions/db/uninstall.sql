DROP TABLE IF EXISTS plugin_svntodimensions_log;
DROP TABLE IF EXISTS plugin_svntodimensions_parameters;
DELETE FROM service WHERE short_name='svntodimensions';
