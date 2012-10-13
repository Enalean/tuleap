DROP TABLE IF EXISTS tracker_workflow;
DROP TABLE IF EXISTS tracker_workflow_transition;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_date;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_int;
DROP TABLE IF EXISTS tracker_workflow_transition_postactions_field_float;

DROP TABLE IF EXISTS widget_renderer;

DROP TABLE IF EXISTS tracker;
DROP TABLE IF EXISTS tracker_field;
DROP TABLE IF EXISTS tracker_field_int;
DROP TABLE IF EXISTS tracker_field_float;
DROP TABLE IF EXISTS tracker_field_text;
DROP TABLE IF EXISTS tracker_field_string;
DROP TABLE IF EXISTS tracker_field_msb;
DROP TABLE IF EXISTS tracker_field_date;
DROP TABLE IF EXISTS tracker_field_list;
DROP TABLE IF EXISTS tracker_field_computed;
DROP TABLE IF EXISTS tracker_field_openlist;
DROP TABLE IF EXISTS tracker_field_openlist_value;
DROP TABLE IF EXISTS tracker_field_list_bind_users;
DROP TABLE IF EXISTS tracker_field_list_bind_static;
DROP TABLE IF EXISTS tracker_field_list_bind_defaultvalue;
DROP TABLE IF EXISTS tracker_field_list_bind_static_value;
DROP TABLE IF EXISTS tracker_changeset;
DROP TABLE IF EXISTS tracker_changeset_comment;
DROP TABLE IF EXISTS tracker_changeset_value;
DROP TABLE IF EXISTS tracker_changeset_value_file;
DROP TABLE IF EXISTS tracker_changeset_value_int;
DROP TABLE IF EXISTS tracker_changeset_value_float;
DROP TABLE IF EXISTS tracker_changeset_value_text;
DROP TABLE IF EXISTS tracker_changeset_value_date;
DROP TABLE IF EXISTS tracker_changeset_value_list;
DROP TABLE IF EXISTS tracker_changeset_value_openlist;
DROP TABLE IF EXISTS tracker_changeset_value_artifactlink;
DROP TABLE IF EXISTS tracker_changeset_value_permissionsonartifact;
DROP TABLE IF EXISTS tracker_fileinfo;
DROP TABLE IF EXISTS tracker_hierarchy;
DROP TABLE IF EXISTS tracker_report;
DROP TABLE IF EXISTS tracker_report_renderer;
DROP TABLE IF EXISTS tracker_report_renderer_table;
DROP TABLE IF EXISTS tracker_report_renderer_table_sort;
DROP TABLE IF EXISTS tracker_report_renderer_table_columns;
DROP TABLE IF EXISTS tracker_report_renderer_table_functions_aggregates;
DROP TABLE IF EXISTS tracker_report_criteria;
DROP TABLE IF EXISTS tracker_report_criteria_date_value;
DROP TABLE IF EXISTS tracker_report_criteria_alphanum_value;
DROP TABLE IF EXISTS tracker_report_criteria_file_value;
DROP TABLE IF EXISTS tracker_report_criteria_list_value;
DROP TABLE IF EXISTS tracker_report_criteria_openlist_value;
DROP TABLE IF EXISTS tracker_report_criteria_permissionsonartifact_value;
DROP TABLE IF EXISTS tracker_field_list_bind_decorator;
DROP TABLE IF EXISTS tracker_artifact;
DROP TABLE IF EXISTS tracker_artifact_priority;
DROP TABLE IF EXISTS tracker_tooltip;
DROP TABLE IF EXISTS tracker_global_notification;
DROP TABLE IF EXISTS tracker_watcher;
DROP TABLE IF EXISTS tracker_notification_role;
DROP TABLE IF EXISTS tracker_notification_event;
DROP TABLE IF EXISTS tracker_notification;
DROP TABLE IF EXISTS tracker_notification_role_default;
DROP TABLE IF EXISTS tracker_notification_event_default;
DROP TABLE IF EXISTS tracker_canned_response;
DROP TABLE IF EXISTS tracker_staticfield_richtext;
DROP TABLE IF EXISTS tracker_semantic_title;
DROP TABLE IF EXISTS tracker_semantic_status;
DROP TABLE IF EXISTS tracker_semantic_contributor;
DROP TABLE IF EXISTS tracker_perm;
DROP TABLE IF EXISTS tracker_rule;
DROP TABLE IF EXISTS tracker_reminder;

DELETE FROM permissions WHERE permission_type LIKE 'PLUGIN_TRACKER_%';
DELETE FROM permissions_values WHERE permission_type LIKE 'PLUGIN_TRACKER_%';

DELETE FROM service WHERE short_name = 'plugin_tracker';

-- Cleanup references
DELETE reference_group FROM reference_group INNER JOIN reference ON (reference_group.reference_id = reference.id) WHERE reference.service_short_name = 'plugin_tracker';
DELETE FROM reference WHERE service_short_name = 'plugin_tracker';
DELETE FROM cross_references WHERE source_type = 'plugin_tracker_artifact' OR target_type = 'plugin_tracker_artifact';

DELETE FROM user_preferences WHERE preference_name LIKE 'tracker\_%\_last\_renderer';
DELETE FROM user_preferences WHERE preference_name LIKE 'tracker\_%\_last\_report';

