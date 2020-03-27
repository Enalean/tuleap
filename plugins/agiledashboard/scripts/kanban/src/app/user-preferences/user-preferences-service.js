export default UserPreferencesService;

UserPreferencesService.$inject = ["Restangular"];

function UserPreferencesService(Restangular) {
    return {
        setPreference: setPreference,
    };

    function setPreference(user_id, key, value) {
        return Restangular.one("users", user_id).all("preferences").patch({
            key: key,
            value: value,
        });
    }
}
