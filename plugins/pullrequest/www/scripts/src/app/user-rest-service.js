export default UserRestService;

UserRestService.$inject = ["$http", "$q", "ErrorModalService"];

function UserRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getUser
    });

    function getUser(user_id) {
        return $http
            .get("/api/v1/users/" + user_id, {
                cache: true
            })
            .then(function(response) {
                return response.data;
            })
            .catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
