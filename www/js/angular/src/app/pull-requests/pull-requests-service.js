angular
    .module('tuleap.pull-request')
    .service('PullRequestsService', PullRequestsService);

PullRequestsService.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestsRestService'
];

function PullRequestsService(
    lodash,
    SharedPropertiesService,
    PullRequestsRestService
) {
    var self = this;

    lodash.extend(self, {
        pull_requests           : SharedPropertiesService.getPullRequests(),
        getPullRequests         : getPullRequests,
        pull_requests_pagination: {
            limit : 50,
            offset: 0
        }
    });

    function getPullRequests(repository_id, limit, offset) {
        return PullRequestsRestService.getPullRequests(repository_id, limit, offset)
            .then(function(response) {
                self.pull_requests.push.apply(self.pull_requests, response.data.collection);

                var headers = response.headers();
                var total   = headers['x-pagination-size'];

                if ((limit + offset) < total) {
                    return getPullRequests(repository_id, limit, offset + limit);
                }

                return self.pull_requests;
            }).then(function() {
                self.pull_requests.forEach(function(pullRequest) {
                    var repoId = parseInt(repository_id, 10);
                    var repositorySrc = pullRequest.repository;
                    var repositoryDest = pullRequest.repository_dest;
                    repositorySrc.isCurrent = (repositorySrc.id === repoId);
                    repositoryDest.isCurrent = (repositoryDest.id === repoId);
                    repositorySrc.isFork = (repositorySrc.id !== repositoryDest.id);
                });
                lodash.reverse(self.pull_requests);
                return self.pull_requests;
            });
    }
}
