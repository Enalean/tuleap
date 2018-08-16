export default PullRequestController;

PullRequestController.$inject = ["$state", "PullRequestRestService", "SharedPropertiesService"];

function PullRequestController($state, PullRequestRestService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        init,
        $state
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
