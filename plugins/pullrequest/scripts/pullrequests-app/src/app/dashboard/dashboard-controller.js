export default DashboardController;

DashboardController.$inject = [
    "PullRequestCollectionService",
    "PullRequestService",
    "TooltipService",
    "$element",
];

function DashboardController(
    PullRequestCollectionService,
    PullRequestService,
    TooltipService,
    $element,
) {
    const self = this;

    Object.assign(self, {
        areClosedPullRequestsFullyLoaded:
            PullRequestCollectionService.areClosedPullRequestsFullyLoaded,
        areOpenPullRequestsFullyLoaded: PullRequestCollectionService.areOpenPullRequestsFullyLoaded,
        isThereAtLeastOneClosedPullRequest:
            PullRequestCollectionService.isThereAtLeastOneClosedPullRequest,
        isThereAtLeastOneOpenpullRequest:
            PullRequestCollectionService.isThereAtLeastOneOpenpullRequest,
        pull_requests: PullRequestCollectionService.all_pull_requests,
        isPullRequestClosed: PullRequestService.isPullRequestClosed,
        valid_status_keys: PullRequestService.valid_status_keys,

        loading_pull_requests: true,

        areClosedPullRequestsHidden,
        loadClosedPullRequests,
        $onInit: init,
    });

    let closed_pull_requests_hidden;

    function init() {
        self.loading_pull_requests = true;
        closed_pull_requests_hidden = true;

        let promise;
        if (PullRequestCollectionService.areAllPullRequestsFullyLoaded()) {
            promise = PullRequestCollectionService.loadAllPullRequests();
        } else {
            promise = PullRequestCollectionService.loadOpenPullRequests();
        }

        return promise
            .then(() => {
                TooltipService.setupTooltips($element[0]);
            })
            .finally(() => {
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
            .then(() => {
                TooltipService.setupTooltips($element[0]);
                closed_pull_requests_hidden = false;
            })
            .finally(() => {
                self.loading_pull_requests = false;
            });
    }

    function areClosedPullRequestsHidden() {
        return closed_pull_requests_hidden;
    }
}
