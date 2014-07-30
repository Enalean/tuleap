var testing = angular.module('testing', [
    'ui.router',
    'campaign-list',
    'shared-properties'
])

.config(function($stateProvider, $urlRouterProvider) {
    $urlRouterProvider.otherwise('/campaigns');
})

.controller('TestingCtrl', ['$scope', 'shared-properties-service', function ($scope, SharedPropertiesService) {
    $scope.init = function (campaign_tracker_id, test_definition_tracker_id, test_execution_tracker_id) {
        SharedPropertiesService.setCampaignTrackerId(campaign_tracker_id);
        SharedPropertiesService.setTestDefinitionTrackerId(test_definition_tracker_id);
        SharedPropertiesService.setTestExecutionTrackerId(test_execution_tracker_id);
    };
}]);
