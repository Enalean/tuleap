var testing = angular.module('testing', [
    'ui.router',
    'campaign-list',
    'shared-properties'
])

.config(function($stateProvider, $urlRouterProvider) {
    $urlRouterProvider.otherwise('/campaigns');
})

.controller('TestingCtrl', ['$scope', 'shared-properties-service', function ($scope, SharedPropertiesService) {
    $scope.init = function (project_id, test_definition_tracker_id, test_execution_tracker_id) {
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setTestDefinitionTrackerId(test_definition_tracker_id);
        SharedPropertiesService.setTestExecutionTrackerId(test_execution_tracker_id);
    };
}]);
