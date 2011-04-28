DROP TABLE IF EXISTS plugin_salome_proxy;
DROP TABLE IF EXISTS plugin_salome_configuration;
DROP TABLE IF EXISTS plugin_salome_activatedplugins;

DELETE FROM service WHERE short_name='salome';
