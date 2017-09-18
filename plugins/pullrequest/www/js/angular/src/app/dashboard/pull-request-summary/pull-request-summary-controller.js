angular
    .module('tuleap.pull-request')
    .controller('PullRequestSummaryController', PullRequestSummaryController);

PullRequestSummaryController.$inject = [
    '$state',
    'lodash',
    'PullRequestService',
    'UserRestService'
];

function PullRequestSummaryController(
    $state,
    _,
    PullRequestService,
    UserRestService
) {
    var self = this;

    _.extend(self, {
        author: {},

        init        : init,
        goToOverview: goToOverview,
        isAbandoned : isAbandoned,
        isMerged    : isMerged
    });

    self.init();

    function init() {
        UserRestService.getUser(self.pull_request.user_id).then(function(user) {
            self.author = user;
        });
    }

    function isAbandoned() {
        return (self.pull_request.status === PullRequestService.valid_status_keys.abandon);
    }

    function isMerged() {
        return (self.pull_request.status === PullRequestService.valid_status_keys.merge);
    }

    function goToOverview() {
        $state.go('overview', { id: self.pull_request.id });
    }
}
