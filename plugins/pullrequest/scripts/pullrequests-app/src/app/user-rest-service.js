export default UserRestService;

UserRestService.$inject = ["$http", "$q", "ErrorModalService"];

function UserRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getUser,
        getPreference,
        setPreference,
    });

    function getUser(user_id) {
        return $http
            .get("/api/v1/users/" + user_id, {
                cache: true,
            })
            .then(function (response) {
                return response.data;
            })
            .catch(function (response) {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }

    function getPreference(user_id, key) {
        return $http
            .get(`/api/v1/users/${user_id}/preferences`, { params: { key } })
            .then((response) => response.data)
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }

    function setPreference(user_id, key, value) {
        return $http
            .patch(`/api/v1/users/${user_id}/preferences`, {
                key,
                value,
            })
            .catch(() => {
                // Do nothing
            });
    }
}
