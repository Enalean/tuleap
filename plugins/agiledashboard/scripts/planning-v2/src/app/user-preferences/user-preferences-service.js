export default UserPreferencesService;

UserPreferencesService.$inject = ["Restangular"];

function UserPreferencesService(Restangular) {
    var rest = Restangular.withConfig(function (RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl("/api/v1");
    });

    return {
        setPreference: setPreference,
    };

    function setPreference(user_id, key, value) {
        return rest.one("users", user_id).all("preferences").patch({
            key: key,
            value: value,
        });
    }
}
