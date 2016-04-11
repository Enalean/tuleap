angular
    .module('tuleap.pull-request')
    .controller('PullRequestsController', PullRequestsController);

PullRequestsController.$inject = [
    '$state',
    'lodash',
    'SharedPropertiesService',
    'PullRequestsRestService',
    'PullRequestService'
];

function PullRequestsController(
    $state,
    lodash,
    SharedPropertiesService,
    PullRequestsRestService,
    PullRequestService
) {
    var self = this;

    lodash.extend(self, {
        valid_status_keys       : PullRequestService.valid_status_keys,
        repository_id           : SharedPropertiesService.getRepositoryId(),
        pull_requests           : [],
        loading_pull_requests   : true,
        pull_requests_pagination: {
            limit : 50,
            offset: 0
        },
        selected_pull_request    : {},
        defineSelectedPullRequest: defineSelectedPullRequest,
        loadPullRequest          : loadPullRequest
    });

    getPullRequests(self.pull_requests_pagination.limit, self.pull_requests_pagination.offset);

    function getPullRequests(limit, offset) {
        return PullRequestsRestService.getPullRequests(self.repository_id, limit, offset)
            .then(function(response) {
                self.pull_requests.push.apply(self.pull_requests, response.data.collection);

                defineSelectedPullRequest();

                var headers = response.headers();
                var total   = headers['x-pagination-size'];

                if ((limit + offset) < total) {
                    return getPullRequests(limit, offset + limit);
                }

                return self.pull_requests;
            }).finally(function() {
                self.loading_pull_requests = false;
            });
    }

    function defineSelectedPullRequest() {
        if ($state.current.name === 'pull-requests') {
            self.selected_pull_request = self.pull_requests[0];

            self.loadPullRequest(self.selected_pull_request);
        } else {
            lodash.forEach(self.pull_requests, function(pull_request) {
                if (pull_request.id === SharedPropertiesService.getPullRequest().id) {
                    self.selected_pull_request = pull_request;
                    SharedPropertiesService.setPullRequest(self.selected_pull_request);
                }
            });
        }
    }

    function loadPullRequest(pull_request) {
        SharedPropertiesService.setPullRequest(pull_request);
        $state.go('pull-request', { id: pull_request.id });
    }
}
