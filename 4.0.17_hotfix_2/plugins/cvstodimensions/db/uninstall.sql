DROP TABLE IF EXISTS plugin_cvstodimensions_log;
DROP TABLE IF EXISTS plugin_cvstodimensions_parameters;
DROP TABLE IF EXISTS plugin_cvstodimensions_modules;
DELETE FROM service WHERE short_name='cvstodimensions';
