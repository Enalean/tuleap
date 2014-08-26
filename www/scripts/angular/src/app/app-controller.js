angular
    .module('testing')
    .controller('TestingCtrl', TestingCtrl);

TestingCtrl.$inject = ['$scope', 'amMoment', 'gettextCatalog', 'SharedPropertiesService'];

function TestingCtrl($scope, amMoment, gettextCatalog, SharedPropertiesService) {
    $scope.init = function (project_id, current_user, lang) {
        SharedPropertiesService.setProjectId(project_id);
        SharedPropertiesService.setCurrentUser(current_user);
        amMoment.changeLanguage(lang);
        gettextCatalog.setCurrentLanguage(lang);
    };
}
