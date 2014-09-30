angular
    .module('user')
    .service('UserService', UserService);

UserService.$inject = ['Restangular'];

function UserService(Restangular) {
    var baseurl = '/api/v1',
        rest = Restangular.withConfig(setRestangularConfig);

    return {
        generateTokenForCurrentUser: generateTokenForCurrentUser,
        getUser: getUser
    };

    function setRestangularConfig(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl(baseurl);
    }

    function generateTokenForCurrentUser(credentials) {
        return rest.all('tokens').post({
            username: credentials.username,
            password: credentials.password
        });
    }

    function getUser(user_id) {
        return rest.one('users', user_id).get();
    }
}