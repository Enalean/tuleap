import { noop } from "angular";

export default PullRequestCollectionService;

PullRequestCollectionService.$inject = [
    "SharedPropertiesService",
    "PullRequestService",
    "PullRequestCollectionRestService",
];

function PullRequestCollectionService(
    SharedPropertiesService,
    PullRequestService,
    PullRequestCollectionRestService,
) {
    const self = this;

    Object.assign(self, {
        areAllPullRequestsFullyLoaded,
        areClosedPullRequestsFullyLoaded,
        areOpenPullRequestsFullyLoaded,
        isThereAtLeastOneClosedPullRequest,
        isThereAtLeastOneOpenpullRequest,
        loadAllPullRequests,
        loadClosedPullRequests,
        loadOpenPullRequests,
        search,

        all_pull_requests: [],
    });

    let open_pull_requests_loaded = false;
    let closed_pull_requests_loaded = false;
    let there_is_at_least_one_open_pull_request = false;
    let there_is_at_least_one_closed_pull_request = false;

    function loadAllPullRequests() {
        const repository_id = SharedPropertiesService.getRepositoryId();

        return PullRequestCollectionRestService.getAllPullRequests(repository_id).then(
            (pull_requests) => {
                const closed_pull_requests = [];
                const open_pull_requests = [];

                pull_requests.forEach((pull_request) => {
                    if (PullRequestService.isPullRequestClosed(pull_request)) {
                        closed_pull_requests.push(pull_request);
                        return;
                    }

                    open_pull_requests.push(pull_request);
                });

                there_is_at_least_one_open_pull_request = open_pull_requests.length > 0;
                there_is_at_least_one_closed_pull_request = closed_pull_requests.length > 0;

                resetAllPullRequests([...open_pull_requests, ...closed_pull_requests]);

                open_pull_requests_loaded = true;
                closed_pull_requests_loaded = true;
            },
        );
    }

    function loadOpenPullRequests() {
        const repository_id = SharedPropertiesService.getRepositoryId();

        let callback = progressivelyLoadCallback;
        if (self.areOpenPullRequestsFullyLoaded()) {
            callback = noop;
        }

        return PullRequestCollectionRestService.getAllOpenPullRequests(
            repository_id,
            callback,
        ).then(function (open_pull_requests) {
            if (!self.areClosedPullRequestsFullyLoaded()) {
                resetAllPullRequests(open_pull_requests);
            }

            there_is_at_least_one_open_pull_request = open_pull_requests.length > 0;
            open_pull_requests_loaded = true;
        });
    }

    function loadClosedPullRequests() {
        const repository_id = SharedPropertiesService.getRepositoryId();

        return PullRequestCollectionRestService.getAllClosedPullRequests(
            repository_id,
            progressivelyLoadCallback,
        ).then(function (closed_pull_requests) {
            there_is_at_least_one_closed_pull_request = closed_pull_requests.length > 0;
            closed_pull_requests_loaded = true;
        });
    }

    function progressivelyLoadCallback(pull_requests) {
        self.all_pull_requests.push(...pull_requests);
    }

    function resetAllPullRequests(pull_requests) {
        self.all_pull_requests.length = 0;
        self.all_pull_requests.push(...pull_requests);
    }

    function search(pull_request_id) {
        return self.all_pull_requests.find(({ id }) => id === pull_request_id);
    }

    function areAllPullRequestsFullyLoaded() {
        return self.areOpenPullRequestsFullyLoaded() && self.areClosedPullRequestsFullyLoaded();
    }

    function areOpenPullRequestsFullyLoaded() {
        return open_pull_requests_loaded;
    }

    function areClosedPullRequestsFullyLoaded() {
        return closed_pull_requests_loaded;
    }

    function isThereAtLeastOneClosedPullRequest() {
        return there_is_at_least_one_closed_pull_request;
    }
    function isThereAtLeastOneOpenpullRequest() {
        return there_is_at_least_one_open_pull_request;
    }
}
