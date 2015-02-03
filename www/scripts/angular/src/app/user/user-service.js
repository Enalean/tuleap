angular
    .module('user')
    .service('UserService', UserService);

UserService.$inject = ['$cookies', '$state', 'Restangular', 'SharedPropertiesService'];

function UserService($cookies, $state, Restangular, SharedPropertiesService) {
    var baseurl = '/api/v1',
        rest = Restangular.withConfig(setRestangularConfig);

    return {
        getUser: getUser,
        login: login
    };

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getUser(user_id) {
        return rest.one('users', user_id).get();
    }

    function login() {
        var user_id = $cookies.TULEAP_user_id,
            token   = $cookies.TULEAP_user_token;

        if (angular.isUndefined(user_id) || angular.isUndefined(token)) {
            user_id = $cookies.CODENDI_user_id,
            token   = $cookies.CODENDI_user_token;
        }

        getUser(user_id).then(function(response) {
            var user = response.data.plain();
            user.token = token;

            SharedPropertiesService.setCurrentUser(user);

            $state.go('campaigns.list');
        });
    }
}