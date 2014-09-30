angular
    .module('user')
    .config(UserConfig);

UserConfig.$inject = ['$stateProvider'];

function UserConfig($stateProvider) {
    $stateProvider
        .state('login', {
            authenticate: false,
            url:         '/login',
            controller:  'UserLoginCtrl',
            templateUrl: 'user/user-login.tpl.html',
            data: {
                ncyBreadcrumbLabel: '{{ login_breadcrumb_label }}'
            }
        });
}
