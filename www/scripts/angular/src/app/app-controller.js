angular
    .module('trafficlights')
    .controller('TrafficlightsCtrl', TrafficlightsCtrl);

TrafficlightsCtrl.$inject = ['$scope', 'amMoment', 'gettextCatalog', 'SharedPropertiesService', 'UserService'];

function TrafficlightsCtrl($scope, amMoment, gettextCatalog, SharedPropertiesService, UserService) {
    $scope.init = function(node_server_id, project_id, lang, current_user, cookies_prefix) {
        SharedPropertiesService.setCurrentUser(UserService.prepareCurrentUser(current_user, cookies_prefix));
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setNodeServerAddress(node_server_id);

        amMoment.changeLocale(lang);
        gettextCatalog.setCurrentLanguage(lang);
    };
}
