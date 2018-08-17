export default UserPreferencesService;

UserPreferencesService.$inject = ["Restangular", "$q"];

function UserPreferencesService(Restangular, $q) {
    return {
        setPreference: setPreference
    };

    function setPreference(user_id, key, value) {
        return Restangular.one("users", user_id)
            .all("preferences")
            .patch({
                key: key,
                value: value
            });
    }
}
