export default SharedPropertiesService;

function SharedPropertiesService() {
    let property = {
        detailed_view_key: "detailed-view",
        compact_view_key: "compact-view",
        user_id: undefined,
        kanban: undefined,
        view_mode: undefined,
        user_is_admin: false,
        project_id: undefined,
        nodejs_server: undefined,
        nodejs_server_version: undefined,
        uuid: undefined,
        dashboard_dropdown: undefined,
        widget_id: 0,
        kanban_url: "",
    };

    return {
        getUserId() {
            return property.user_id;
        },
        setUserId(user_id) {
            property.user_id = user_id;
        },
        doesUserPrefersCompactCards() {
            return property.view_mode !== property.detailed_view_key;
        },
        setUserPrefersCompactCards(is_collapsed) {
            return (property.view_mode = is_collapsed
                ? property.compact_view_key
                : property.detailed_view_key);
        },
        getViewMode() {
            return property.view_mode;
        },
        setViewMode(view_mode) {
            property.view_mode = view_mode;
        },
        getKanban() {
            return property.kanban;
        },
        setKanban(kanban) {
            property.kanban = kanban;
        },
        getUserIsAdmin() {
            return property.user_is_admin;
        },
        setUserIsAdmin(user_is_admin) {
            property.user_is_admin = user_is_admin;
        },
        setProjectId(project_id) {
            property.project_id = project_id;
        },
        getProjectId() {
            return property.project_id;
        },
        getNodeServerAddress() {
            return property.nodejs_server;
        },
        setNodeServerAddress(nodejs_server) {
            property.nodejs_server = nodejs_server;
        },
        thereIsNodeServerAddress() {
            return Boolean(property.nodejs_server);
        },
        getUUID() {
            return property.uuid;
        },
        setUUID(uuid) {
            property.uuid = uuid;
        },
        setNodeServerVersion(nodejs_server_version) {
            property.nodejs_server_version = nodejs_server_version;
        },
        getNodeServerVersion() {
            return property.nodejs_server_version;
        },
        setDashboardDropdown(dashboard_dropdown) {
            property.dashboard_dropdown = dashboard_dropdown;
        },
        getDashboardDropdown() {
            return property.dashboard_dropdown;
        },
        getUserIsOnWidget() {
            return property.widget_id !== 0;
        },
        setWidgetId(widget_id) {
            property.widget_id = widget_id;
        },
        getWidgetId() {
            return property.widget_id;
        },
        getKanbanUrl() {
            return property.kanban_url;
        },
        setKanbanUrl(kanban_url) {
            property.kanban_url = kanban_url;
        },
        setSelectedTrackerReportId(report_id) {
            property.selected_tracker_report_id = report_id;
        },
        getSelectedTrackerReportId() {
            return property.selected_tracker_report_id;
        },
    };
}
