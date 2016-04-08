angular
    .module('trafficlights')
    .controller('TrafficlightsCtrl', TrafficlightsCtrl);

TrafficlightsCtrl.$inject = [
    '$scope',
    'amMoment',
    'gettextCatalog',
    'SharedPropertiesService',
    'UUIDGeneratorService'
];

function TrafficlightsCtrl(
    $scope,
    amMoment,
    gettextCatalog,
    SharedPropertiesService,
    UUIDGeneratorService
) {
    $scope.init = function(nodejs_server, project_id, lang, current_user) {
        SharedPropertiesService.setCurrentUser(current_user);
        SharedPropertiesService.setProjectId(project_id);
        var uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("0.0.3");
        SharedPropertiesService.setNodeServerAddress(nodejs_server);

        amMoment.changeLocale(lang);
        gettextCatalog.setCurrentLanguage(lang);
    };
}