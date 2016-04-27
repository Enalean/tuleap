angular
    .module('tuleap.pull-request')
    .controller('OverviewController', OverviewController);

OverviewController.$inject = [
    '$state',
    'lodash',
    'SharedPropertiesService',
    'PullRequestService',
    'PullRequestRestService',
    'UserRestService'
];

function OverviewController(
    $state,
    lodash,
    SharedPropertiesService,
    PullRequestService,
    PullRequestRestService,
    UserRestService
) {
    var self = this;

    lodash.extend(self, {
        $state           : $state,
        valid_status_keys: PullRequestService.valid_status_keys,
        pull_request     : SharedPropertiesService.getPullRequest(),
        author           : {},
        merge            : merge,
        abandon          : abandon
    });

    refreshPullRequest();

    function refreshPullRequest() {
        if (! lodash.has(self.pull_request, 'status')) {
            PullRequestRestService.getPullRequest(self.pull_request.id).then(function(pull_request) {
                self.pull_request = pull_request;
                refreshAuthor();
            });
        } else {
            refreshAuthor();
        }
    }

    function refreshAuthor() {
        UserRestService.getUser(self.pull_request.user_id).then(function(user) {
            self.author = user;
        });
    }

    function merge() {
        PullRequestService.merge(self.pull_request);
    }

    function abandon() {
        PullRequestService.abandon(self.pull_request);
    }
}
