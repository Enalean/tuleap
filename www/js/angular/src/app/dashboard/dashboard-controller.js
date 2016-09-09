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
        areClosedPullRequestsFullyLoaded  : PullRequestCollectionService.areClosedPullRequestsFullyLoaded,
        areClosedPullRequestsHidden       : areClosedPullRequestsHidden,
        areOpenPullRequestsFullyLoaded    : PullRequestCollectionService.areOpenPullRequestsFullyLoaded,
        hideClosedPullRequests            : hideClosedPullRequests,
        init                              : init,
        isPullRequestClosed               : PullRequestService.isPullRequestClosed,
        isThereAtLeastOneClosedPullRequest: PullRequestCollectionService.isThereAtLeastOneClosedPullRequest,
        isThereAtLeastOneOpenpullRequest  : PullRequestCollectionService.isThereAtLeastOneOpenpullRequest,
        loadClosedPullRequests            : loadClosedPullRequests,

        loading_pull_requests: true,
        pull_requests        : PullRequestCollectionService.all_pull_requests,
        valid_status_keys    : PullRequestService.valid_status_keys
    });

    self.init();

    var closed_pull_requests_hidden;

    function init() {
        self.loading_pull_requests  = true;
        closed_pull_requests_hidden = true;

        var promise;
        if (PullRequestCollectionService.areAllPullRequestsFullyLoaded()) {
            promise = PullRequestCollectionService.loadAllPullRequests();
        } else {
            promise = PullRequestCollectionService.loadOpenPullRequests();
        }

        return promise
        .then(function() {
            TooltipService.setupTooltips();
        })
        .finally(function() {
            self.loading_pull_requests = false;
        });
    }

    function loadClosedPullRequests() {
        if (PullRequestCollectionService.areClosedPullRequestsFullyLoaded()) {
            closed_pull_requests_hidden = false;
            return false;
        }

        self.loading_pull_requests = true;

        return PullRequestCollectionService.loadClosedPullRequests()
        .then(function() {
            TooltipService.setupTooltips();
            closed_pull_requests_hidden = false;
        })
        .finally(function() {
            self.loading_pull_requests = false;
        });
    }

    function areClosedPullRequestsHidden() {
        return closed_pull_requests_hidden;
    }

    function hideClosedPullRequests() {
        closed_pull_requests_hidden = true;
    }
}
