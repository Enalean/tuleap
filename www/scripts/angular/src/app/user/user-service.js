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
        getCurrentUserFromCookies: getCurrentUserFromCookies,
        prepareCurrentUser       : prepareCurrentUser
    };

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function getUser(user_id) {
        return rest.one('users', user_id).get();
    }

    function getCurrentUser(cookies_prefix) {
        var user_id = getUserIdFromCookies(),
            token   = getUserTokenFromCookies(),
            data    = $q.defer();

        if (angular.isUndefined(user_id) || angular.isUndefined(token)) {
            user_id = $cookies[cookies_prefix+'_user_id'];
            token   = $cookies[cookies_prefix+'_user_token'];
        }

        getUser(user_id).then(function(response) {
            var user = response.data.plain();
            user.token = token;

            data.resolve(user);
        });

        return data.promise;
    }

    function prepareCurrentUser(user_json, cookies_prefix) {
        var user = user_json,
        token    = getUserTokenFromCookies();

        if (angular.isUndefined(token)) {
            token = $cookies[cookies_prefix+'_user_token'];
        }

        user.token = token;

        return user;
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
