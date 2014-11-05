angular
    .module('user')
    .controller('UserLogoutCtrl', UserLogoutCtrl);

UserLogoutCtrl.$inject = ['$scope', '$state', 'SharedPropertiesService'];

function UserLogoutCtrl($scope, $state, SharedPropertiesService) {

    logout();

    function logout() {
        SharedPropertiesService.removeCurrentUser();
        $state.go('login');
    }
}