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
    _,
    ErrorModalService
) {
    var self = this;

    _.extend(self, {
        getPullRequest           : getPullRequest,
        updateStatus             : updateStatus,
        updateTitleAndDescription: updateTitleAndDescription
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

    function updateTitleAndDescription(pull_request_id, new_title, new_description) {
        return $http.patch('/api/v1/pull_requests/' + pull_request_id, { title: new_title, description: new_description })
            .catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
