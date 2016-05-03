angular
    .module('tuleap.pull-request')
    .controller('OverviewController', OverviewController);

OverviewController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestService',
    'UserRestService'
];

function OverviewController(
    lodash,
    SharedPropertiesService,
    PullRequestService,
    UserRestService
) {
    var self = this;

    lodash.extend(self, {
        valid_status_keys: PullRequestService.valid_status_keys,
        pull_request     : {},
        author           : {},
        merge            : merge,
        abandon          : abandon
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();
        UserRestService.getUser(self.pull_request.user_id).then(function(user) {
            self.author = user;
        });
    });

    function merge() {
        PullRequestService.merge(self.pull_request);
    }

    function abandon() {
        PullRequestService.abandon(self.pull_request);
    }
}
