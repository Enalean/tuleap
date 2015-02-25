angular
    .module('kanban')
    .controller('ErrorCtrl', ErrorCtrl);

ErrorCtrl.$inject = ['$scope', '$window'];

function ErrorCtrl($scope, $window) {
    $scope.reloading = false;
    $scope.ok        = ok;

    function ok() {
        $scope.reloading = true;
        $window.location.reload();
    }
}