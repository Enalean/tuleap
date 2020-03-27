export default JWTService;

JWTService.$inject = ["Restangular", "jwtHelper"];

function JWTService(Restangular, jwtHelper) {
    var rest = Restangular.withConfig(function (RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    return {
        getJWT: getJWT,
        getTokenExpiredDate: getTokenExpiredDate,
    };

    function getJWT() {
        return rest
            .one("jwt")
            .get()
            .then(function (response) {
                return response.data;
            });
    }

    function getTokenExpiredDate(token) {
        return jwtHelper.getTokenExpirationDate(token);
    }
}
