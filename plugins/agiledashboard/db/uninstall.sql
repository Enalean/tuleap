DROP TABLE IF EXISTS plugin_agiledashboard_planning;
DROP TABLE IF EXISTS plugin_agiledashboard_planning_backlog_tracker;
DROP TABLE IF EXISTS plugin_agiledashboard_semantic_initial_effort;
DROP TABLE IF EXISTS plugin_agiledashboard_criteria;
DROP TABLE IF EXISTS plugin_agiledashboard_configuration;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_configuration_column;
DROP TABLE IF EXISTS plugin_agiledashboard_scrum_mono_milestones;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_widget;
DROP TABLE IF EXISTS plugin_agiledashboard_kanban_widget_config;
DROP TABLE IF EXISTS plugin_agiledashboard_semantic_done;
DROP TABLE IF EXISTS plugin_agiledashboard_tracker_field_burnup_cache;

DELETE FROM permissions_values WHERE permission_type IN ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE');

DELETE FROM service WHERE short_name='plugin_agiledashboard';
