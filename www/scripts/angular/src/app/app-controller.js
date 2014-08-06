angular
    .module('testing')
    .controller('TestingCtrl', TestingCtrl);

TestingCtrl.$inject = ['$scope', 'SharedPropertiesService'];

function TestingCtrl($scope, SharedPropertiesService) {
    $scope.init = function (project_id, test_definition_tracker_id, test_execution_tracker_id) {
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setTestDefinitionTrackerId(test_definition_tracker_id);
        SharedPropertiesService.setTestExecutionTrackerId(test_execution_tracker_id);
    };
}
