angular
    .module('tuleap.pull-request')
    .controller('PullRequestsController', PullRequestsController);

PullRequestsController.$inject = [
    '$state',
    'lodash',
    'SharedPropertiesService',
    'PullRequestService'
];

function PullRequestsController(
    $state,
    lodash,
    SharedPropertiesService,
    PullRequestService
) {
    var self = this;

    lodash.extend(self, {
        loading_pull_requests: true,
        valid_status_keys    : PullRequestService.valid_status_keys,
        pull_requests        : [],
        selected_pull_request: {},
        loadPullRequest      : loadPullRequest
    });


    SharedPropertiesService.whenReady().then(function() {
        self.pull_requests = SharedPropertiesService.getPullRequests();
        self.selected_pull_request = SharedPropertiesService.getPullRequest();
    }).finally(function() {
        self.loading_pull_requests = false;
    });

    function loadPullRequest(pull_request) {
        SharedPropertiesService.setPullRequest(pull_request);
        $state.go('overview', { id: pull_request.id });
    }
}
