angular
    .module('testing')
    .controller('TestingCtrl', TestingCtrl);

TestingCtrl.$inject = ['$scope', 'SharedPropertiesService'];

function TestingCtrl($scope, SharedPropertiesService) {
    $scope.init = function (project_id, current_user) {
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setCurrentUser(current_user);
    };
}
