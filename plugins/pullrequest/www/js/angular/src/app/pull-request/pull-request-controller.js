angular
    .module('tuleap.pull-request')
    .controller('PullRequestController', PullRequestController);

PullRequestController.$inject = [
    '$state',
    'lodash',
    'PullRequestRestService',
    'SharedPropertiesService'
];

function PullRequestController(
    $state,
    _,
    PullRequestRestService,
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
        var promise = PullRequestRestService.getPullRequest(pull_request_id);

        SharedPropertiesService.setReadyPromise(promise);

        promise.then(function(pullrequest) {
            SharedPropertiesService.setPullRequest(pullrequest);
        });
    }
}
