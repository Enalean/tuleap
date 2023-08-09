export default SharedPropertiesService;

function SharedPropertiesService() {
    let property = {
        detailed_view_key: "detailed-view",
        compact_view_key: "compact-view",
        user_id: undefined,
        kanban: undefined,
        view_mode: undefined,
        user_is_admin: false,
        is_node_server_connected: false,
        nodejs_server_version: undefined,
        uuid: undefined,
        dashboard_dropdown: undefined,
        widget_id: 0,
        kanban_url: "",
        kanban_homepage_url: "",
        mercure_enabled: false,
        is_mercure_server_connected: false,
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
        setIsNodeServerConnected(is_node_server_connected) {
            property.is_node_server_connected = is_node_server_connected;
        },
        isNodeServerConnected() {
            return property.is_node_server_connected;
        },
        setIsMercureServerConnected(is_mercure_server_connected) {
            property.is_mercure_server_connected = is_mercure_server_connected;
        },
        isMercureServerConnected() {
            return property.is_mercure_server_connected;
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
        getKanbanHomepageUrl() {
            return property.kanban_homepage_url;
        },
        setKanbanHomepageUrl(kanban_homepage_url) {
            property.kanban_homepage_url = kanban_homepage_url;
        },
        setSelectedTrackerReportId(report_id) {
            property.selected_tracker_report_id = report_id;
        },
        getSelectedTrackerReportId() {
            return property.selected_tracker_report_id;
        },
        getMercureEnabled() {
            return property.mercure_enabled;
        },
        setMercureEnabled(mercure_enabled) {
            property.mercure_enabled = mercure_enabled;
        },
    };
}
