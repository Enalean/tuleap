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

    function init(kanban, dashboard_dropdown, user_id, user_is_admin, lang, project_id, view_mode, nodejs_server) {
        SharedPropertiesService.setUserId(user_id);
        SharedPropertiesService.setKanban(kanban);
        SharedPropertiesService.setDashboardDropdown(dashboard_dropdown);
        SharedPropertiesService.setUserIsAdmin(user_is_admin);
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setViewMode(view_mode);
        gettextCatalog.setCurrentLanguage(lang);
        amMoment.changeLocale(lang);
        var uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("1.1.0");
        SharedPropertiesService.setNodeServerAddress(nodejs_server);
    }
}
