angular
    .module('user')
    .controller('UserLoginCtrl', UserLoginCtrl);

UserLoginCtrl.$inject = ['$scope', '$state', 'gettextCatalog', 'UserService', 'SharedPropertiesService'];

function UserLoginCtrl($scope, $state, gettextCatalog, UserService, SharedPropertiesService) {
    $scope.login_breadcrumb_label = gettextCatalog.getString('Login');
    $scope.login                  = login;
    $scope.loading                = false;
    $scope.error_message          = '';
    $scope.credentials            = {
        username: '',
        password: ''
    };

    redirectIfAlreadyLoggedIn();

    function redirectIfAlreadyLoggedIn() {
        if (SharedPropertiesService.getCurrentUser()) {
            $state.go('campaigns.list');
        }
    }

    function login() {
        $scope.loading = true;

        UserService.generateTokenForCurrentUser($scope.credentials).then(function(response) {
            var user_id = response.data.user_id,
                token   = response.data.token;

            UserService.getUser(user_id).then(function(response) {
                var user = response.data.plain();
                user.token = token;

                SharedPropertiesService.setCurrentUser(user);

                $state.go('campaigns.list');

            }, function() {
                $scope.loading       = false;
                $scope.error_message = gettextCatalog.getString('Unable to retrieve the user');
            });

        }, function() {
            $scope.loading       = false;
            $scope.error_message = gettextCatalog.getString('Unable to generate a token: wrong username and/or password.');
        });
    }
}