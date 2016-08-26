angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    '$state',
    '$q',
    'lodash',
    'PullRequestRestService',
    'PullRequestCollectionService',
    'SharedPropertiesService'
];

function PullRequestController(
    $state,
    $q,
    _,
    PullRequestRestService,
    PullRequestCollectionService,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        init: init,

        $state: $state
    });

    self.init();

    function init() {
        var pull_request_id = parseInt($state.params.id, 10);
        var promise;

        if (PullRequestCollectionService.pull_requests_fully_loaded) {
            promise = searchForPullrequest(pull_request_id);
        } else {
            promise = loadPullrequest(pull_request_id);
        }

        SharedPropertiesService.setReadyPromise(promise);

        promise.then(function(pullrequest) {
            SharedPropertiesService.setPullRequest(pullrequest);
        });
    }

    function loadPullrequest(pull_request_id) {
        return PullRequestRestService.getPullRequest(pull_request_id);
    }

    function searchForPullrequest(pull_request_id) {
        return $q(function(resolve, reject) {
            var pullrequest = PullRequestCollectionService.search(pull_request_id);
            if (pullrequest) {
                resolve(pullrequest);
            } else {
                reject();
            }
        });
    }
}
