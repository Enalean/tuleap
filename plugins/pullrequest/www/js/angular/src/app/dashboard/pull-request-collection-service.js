angular
    .module('tuleap.pull-request')
    .service('PullRequestCollectionService', PullRequestCollectionService);

PullRequestCollectionService.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestService',
    'PullRequestCollectionRestService'
];

function PullRequestCollectionService(
    _,
    SharedPropertiesService,
    PullRequestService,
    PullRequestCollectionRestService
) {
    var self = this;

    _.extend(self, {
        areAllPullRequestsFullyLoaded     : areAllPullRequestsFullyLoaded,
        areClosedPullRequestsFullyLoaded  : areClosedPullRequestsFullyLoaded,
        areOpenPullRequestsFullyLoaded    : areOpenPullRequestsFullyLoaded,
        isThereAtLeastOneClosedPullRequest: isThereAtLeastOneClosedPullRequest,
        isThereAtLeastOneOpenpullRequest  : isThereAtLeastOneOpenpullRequest,
        loadAllPullRequests               : loadAllPullRequests,
        loadClosedPullRequests            : loadClosedPullRequests,
        loadOpenPullRequests              : loadOpenPullRequests,
        search                            : search,

        all_pull_requests: []
    });

    var open_pull_requests_loaded                 = false;
    var closed_pull_requests_loaded               = false;
    var there_is_at_least_one_open_pull_request   = false;
    var there_is_at_least_one_closed_pull_request = false;

    function loadAllPullRequests() {
        var repository_id = SharedPropertiesService.getRepositoryId();

        var promise = PullRequestCollectionRestService.getAllPullRequests(repository_id)
        .then(function(pull_requests) {
            var all_pull_requests    = _.partition(pull_requests, PullRequestService.isPullRequestClosed);
            var closed_pull_requests = all_pull_requests[0];
            var open_pull_requests   = all_pull_requests[1];

            there_is_at_least_one_open_pull_request   = (open_pull_requests.length > 0);
            there_is_at_least_one_closed_pull_request = (closed_pull_requests.length > 0);

            resetAllPullRequests(closed_pull_requests.concat(open_pull_requests));

            open_pull_requests_loaded   = true;
            closed_pull_requests_loaded = true;
        });

        return promise;
    }

    function loadOpenPullRequests() {
        var repository_id = SharedPropertiesService.getRepositoryId();

        var callback = progressivelyLoadCallback;
        if (self.areOpenPullRequestsFullyLoaded()) {
            callback = angular.noop;
        }

        var promise = PullRequestCollectionRestService.getAllOpenPullRequests(repository_id, callback)
        .then(function(open_pull_requests) {
            if (! self.areClosedPullRequestsFullyLoaded()) {
                resetAllPullRequests(open_pull_requests);
            }

            there_is_at_least_one_open_pull_request = (open_pull_requests.length > 0);
            open_pull_requests_loaded               = true;
        });

        return promise;
    }

    function loadClosedPullRequests() {
        var repository_id = SharedPropertiesService.getRepositoryId();

        var promise = PullRequestCollectionRestService.getAllClosedPullRequests(repository_id, progressivelyLoadCallback)
        .then(function(closed_pull_requests) {
            there_is_at_least_one_closed_pull_request = (closed_pull_requests.length > 0);
            closed_pull_requests_loaded               = true;
        });

        return promise;
    }

    function progressivelyLoadCallback(pull_requests) {
        _.forEachRight(pull_requests, function(pull_request) {
            self.all_pull_requests.push(pull_request);
        });
    }

    function resetAllPullRequests(pull_requests) {
        emptyArray(self.all_pull_requests);

        _.forEachRight(pull_requests, function(pull_request) {
            self.all_pull_requests.push(pull_request);
        });
    }

    function emptyArray(array) {
        array.length = 0;
    }

    function search(pull_request_id) {
        return _.find(self.all_pull_requests, ['id', pull_request_id]);
    }

    function areAllPullRequestsFullyLoaded() {
        return (self.areOpenPullRequestsFullyLoaded()
            && self.areClosedPullRequestsFullyLoaded());
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
