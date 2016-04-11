angular
    .module('tuleap.pull-request')
    .service('PullRequestRestService', PullRequestRestService);

PullRequestRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function PullRequestRestService(
    $http,
    $q,
    lodash,
    ErrorModalService
) {
    var self = this;

    lodash.extend(self, {
        getPullRequest: getPullRequest,
        updateStatus  : updateStatus
    });

    function getPullRequest(pull_request_id) {
        return $http.get('/api/v1/pull_requests/' + pull_request_id)
            .then(function(response) {
                return response.data;
            }).catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }

    function updateStatus(pull_request_id, status) {
        return $http.patch('/api/v1/pull_requests/' + pull_request_id, { status: status })
            .catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
