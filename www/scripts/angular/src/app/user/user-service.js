angular
    .module('user')
    .service('UserService', UserService);

UserService.$inject = ['$q', '$cookies', 'Restangular'];

function UserService($q, $cookies, Restangular) {
    var baseurl = '/api/v1',
        rest = Restangular.withConfig(setRestangularConfig);

    return {
        getUser                  : getUser,
        getCurrentUser           : getCurrentUser,
        getCurrentUserFromCookies: getCurrentUserFromCookies
    };

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getUser(user_id) {
        return rest.one('users', user_id).get();
    }

    function getCurrentUser() {
        var user_id = getUserIdFromCookies(),
            token   = getUserTokenFromCookies(),
            data    = $q.defer();

        if (angular.isUndefined(user_id) || angular.isUndefined(token)) {
            user_id = $cookies.CODENDI_user_id,
            token   = $cookies.CODENDI_user_token;
        }

        getUser(user_id).then(function(response) {
            var user = response.data.plain();
            user.token = token;

            data.resolve(user);
        });

        return data.promise;
    }

    function getCurrentUserFromCookies() {
        return {
            id   : getUserIdFromCookies(),
            token: getUserTokenFromCookies()
        };
    }

    function getUserIdFromCookies() {
        return $cookies.TULEAP_user_id || $cookies.CODENDI_user_id;
    }

    function getUserTokenFromCookies() {
        return $cookies.TULEAP_user_token || $cookies.CODENDI_user_token;
    }
}
