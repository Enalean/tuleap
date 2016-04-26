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
        var uuid = UUIDGeneratorService.generateUUID();
        SharedPropertiesService.setUUID(uuid);
        SharedPropertiesService.setNodeServerVersion("0.0.3");
        SharedPropertiesService.setNodeServerAddress(nodejs_server);
        current_user.uuid = uuid;
        SharedPropertiesService.setCurrentUser(current_user);
        SharedPropertiesService.setProjectId(project_id);

        amMoment.changeLocale(lang);
        gettextCatalog.setCurrentLanguage(lang);
    };
}