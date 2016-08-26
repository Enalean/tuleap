angular
    .module('tuleap.pull-request')
    .service('PullRequestCollectionService', PullRequestCollectionService);

PullRequestCollectionService.$inject = [
    'lodash',
    'SharedPropertiesService',
    'PullRequestCollectionRestService'
];

function PullRequestCollectionService(
    _,
    SharedPropertiesService,
    PullRequestCollectionRestService
) {
    var self = this;

    _.extend(self, {
        loadPullRequests: loadPullRequests,
        search          : search,

        all_pull_requests         : [],
        pull_requests_fully_loaded: false
    });

    function loadPullRequests() {
        var repository_id = SharedPropertiesService.getRepositoryId();

        var promise = PullRequestCollectionRestService.getAllPullRequests(repository_id, progressivelyLoadCallback)
            .then(function(pull_requests) {
                if (self.pull_requests_fully_loaded) {
                    emptyArray(self.all_pull_requests);

                    _.forEachRight(pull_requests, function(pull_request) {
                        self.all_pull_requests.push(pull_request);
                    });
                }

                self.pull_requests_fully_loaded = true;
            });

        return promise;
    }

    function progressivelyLoadCallback(pull_requests) {
        if (self.pull_requests_fully_loaded) {
            return;
        }

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
}
