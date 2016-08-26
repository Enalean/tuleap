angular
    .module('tuleap.pull-request')
    .controller('DashboardController', DashboardController);

DashboardController.$inject = [
    'lodash',
    'PullRequestCollectionService',
    'PullRequestService',
    'TooltipService'
];

function DashboardController(
    _,
    PullRequestCollectionService,
    PullRequestService,
    TooltipService
) {
    var self = this;

    _.extend(self, {
        init: init,

        loading_pull_requests: true,
        pull_requests        : PullRequestCollectionService.all_pull_requests,
        valid_status_keys    : PullRequestService.valid_status_keys
    });

    self.init();

    function init() {
        self.loading_pull_requests = true;

        return PullRequestCollectionService.loadPullRequests()
        .finally(function() {
            self.loading_pull_requests = false;
            TooltipService.setupTooltips();
        });
    }
}
