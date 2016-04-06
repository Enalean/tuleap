angular
    .module('jwt')
    .service('JWTService', JWTService);

JWTService.$inject = ['Restangular', '$q'];

function JWTService(Restangular, $q) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getJWT: getJWT
    };

    function getJWT() {
        return rest
            .one('jwt')
            .get()
            .then(function (response) {
                return response.data;
            });
    }
}