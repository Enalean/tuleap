angular
    .module('testing')
    .controller('TestingCtrl', TestingCtrl);

TestingCtrl.$inject = ['$scope', 'amMoment', 'gettextCatalog', 'SharedPropertiesService'];

function TestingCtrl($scope, amMoment, gettextCatalog, SharedPropertiesService) {
    $scope.init = function(node_server_id, project_id, lang) {
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setNodeServerAddress(node_server_id);
        amMoment.changeLanguage(lang);
        gettextCatalog.setCurrentLanguage(lang);
    };
}
