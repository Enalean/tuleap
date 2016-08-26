angular
    .module('tuleap.pull-request')
    .service('UserRestService', UserRestService);

UserRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function UserRestService(
    $http,
    $q,
    _,
    ErrorModalService
) {
    var self = this;

    _.extend(self, {
        getUser: getUser
    });

    function getUser(user_id) {
        return $http.get('/api/v1/users/' + user_id, {
            cache: true
        })
        .then(function(response) {
            return response.data;
        }).catch(function(response) {
            ErrorModalService.showError(response);
            return $q.reject(response);
        });
    }
}
