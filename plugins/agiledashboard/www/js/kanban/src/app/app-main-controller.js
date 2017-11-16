export default MainCtrl;

MainCtrl.$inject = [
    '$scope',
    'gettextCatalog',
    'SharedPropertiesService',
    'amMoment',
    'UUIDGeneratorService',
    'FilterTrackerReportService'
];

function MainCtrl(
    $scope,
    gettextCatalog,
    SharedPropertiesService,
    amMoment,
    UUIDGeneratorService,
    FilterTrackerReportService
) {
    $scope.init = init;

    function init(
        kanban,
        dashboard_dropdown,
        filters_tracker_report,
        user_id,
        user_is_admin,
        widget_id,
        lang,
        project_id,
        view_mode,
        nodejs_server,
        kanban_url
    ) {
        const uuid = UUIDGeneratorService.generateUUID();

        FilterTrackerReportService.setFiltersTrackerReport(Object.values(filters_tracker_report));
        SharedPropertiesService.setUserId(user_id);
        SharedPropertiesService.setKanban(kanban);
        SharedPropertiesService.setDashboardDropdown(dashboard_dropdown);
        SharedPropertiesService.setUserIsAdmin(user_is_admin);
        SharedPropertiesService.setWidgetId(widget_id);
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setViewMode(view_mode);
        SharedPropertiesService.setKanbanUrl(kanban_url);
        gettextCatalog.setCurrentLanguage(lang);
        amMoment.changeLocale(lang);
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("1.1.0");
        SharedPropertiesService.setNodeServerAddress(nodejs_server);
    }
}
