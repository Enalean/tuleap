angular
    .module('tuleap.pull-request')
    .service('PullRequestCollectionRestService', PullRequestCollectionRestService);

PullRequestCollectionRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function PullRequestCollectionRestService(
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
