DROP TABLE IF EXISTS plugin_kanban_legacy_configuration;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration_column;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_widget;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_widget_config;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_tracker_reports;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_recently_visited;

DELETE FROM service WHERE short_name = 'plugin_kanban';