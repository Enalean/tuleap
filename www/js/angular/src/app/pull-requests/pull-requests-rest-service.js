angular
    .module('tuleap.pull-request')
    .service('PullRequestsRestService', PullRequestsRestService);

PullRequestsRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function PullRequestsRestService(
    $http,
    $q,
    _,
    ErrorModalService
) {
    var self = this;

    _.extend(self, {
        getPullRequests: getPullRequests
    });

    function getPullRequests(repository_id, limit, offset) {
        return $http.get('/api/v1/git/' + repository_id + '/pull_requests?limit=' + limit + '&offset=' + offset)
            .catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
