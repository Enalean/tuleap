angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestService',
    'PullRequestRestService'
];

function PullRequestController(
    lodash,
    SharedPropertiesService,
    PullRequestService,
    PullRequestRestService
) {
    var self = this;

    lodash.extend(self, {
        valid_status_keys: PullRequestService.valid_status_keys,
        pull_request     : SharedPropertiesService.getPullRequest(),
        merge            : merge,
        abandon          : abandon
    });

    refreshPullRequest();

    function refreshPullRequest() {
        if (! lodash.has(self.pull_request, 'status')) {
            PullRequestRestService.getPullRequest(self.pull_request.id).then(function(pull_request) {
                self.pull_request = pull_request;
            });
        }
    }

    function merge() {
        PullRequestService.merge(self.pull_request);
    }

    function abandon() {
        PullRequestService.abandon(self.pull_request);
    }
}
