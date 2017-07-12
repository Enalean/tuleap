export default MainCtrl;

MainCtrl.$inject = [
    '$scope',
    'gettextCatalog',
    'SharedPropertiesService',
    'amMoment',
    'UUIDGeneratorService'
];

function MainCtrl(
    $scope,
    gettextCatalog,
    SharedPropertiesService,
    amMoment,
    UUIDGeneratorService
) {
    $scope.init = init;

    function init(
        kanban,
        dashboard_dropdown,
        user_id,
        user_is_admin,
        is_widget,
        lang,
        project_id,
        view_mode,
        nodejs_server,
        kanban_url
    ) {
        SharedPropertiesService.setUserId(user_id);
        SharedPropertiesService.setKanban(kanban);
        SharedPropertiesService.setDashboardDropdown(dashboard_dropdown);
        SharedPropertiesService.setUserIsAdmin(user_is_admin);
        SharedPropertiesService.setUserIsOnWidget(is_widget);
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setViewMode(view_mode);
        SharedPropertiesService.setKanbanUrl(kanban_url);
        gettextCatalog.setCurrentLanguage(lang);
        amMoment.changeLocale(lang);
        var uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("1.1.0");
        SharedPropertiesService.setNodeServerAddress(nodejs_server);
    }
}
