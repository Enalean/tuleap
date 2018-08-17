export default ErrorCtrl;

ErrorCtrl.$inject = ["$scope", "$window", "message"];

function ErrorCtrl($scope, $window, message) {
    $scope.reloading = false;
    $scope.ok = ok;
    $scope.message = message;
    $scope.details = false;

    function ok() {
        $scope.reloading = true;
        $window.location.reload();
    }
}
