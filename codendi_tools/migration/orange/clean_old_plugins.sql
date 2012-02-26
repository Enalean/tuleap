-- svntodimensions
DROP TABLE IF EXISTS plugin_svntodimensions_log;
DROP TABLE IF EXISTS plugin_svntodimensions_parameters;
DELETE FROM service WHERE short_name='svntodimensions';
DELETE FROM plugin WHERE name='svntodimensions';

-- cvstodimensions
DROP TABLE IF EXISTS plugin_cvstodimensions_log;
DROP TABLE IF EXISTS plugin_cvstodimensions_parameters;
DROP TABLE IF EXISTS plugin_cvstodimensions_modules;
DELETE FROM service WHERE short_name='cvstodimensions';
DELETE FROM plugin WHERE name='cvstodimensions';

-- salome
DROP TABLE IF EXISTS plugin_salome_proxy;
DROP TABLE IF EXISTS plugin_salome_configuration;
DROP TABLE IF EXISTS plugin_salome_activatedplugins;
DELETE FROM service WHERE short_name='salome';
DELETE FROM plugin WHERE name='salome';

