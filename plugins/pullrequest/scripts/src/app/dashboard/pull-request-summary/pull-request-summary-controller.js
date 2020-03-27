export default PullRequestSummaryController;

PullRequestSummaryController.$inject = ["$state", "PullRequestService", "UserRestService"];

function PullRequestSummaryController($state, PullRequestService, UserRestService) {
    const self = this;

    Object.assign(self, {
        author: {},

        $onInit: init,
        goToOverview,
        isAbandoned,
        isMerged,
    });

    function init() {
        UserRestService.getUser(self.pull_request.user_id).then((user) => {
            self.author = user;
        });
    }

    function isAbandoned() {
        return self.pull_request.status === PullRequestService.valid_status_keys.abandon;
    }

    function isMerged() {
        return self.pull_request.status === PullRequestService.valid_status_keys.merge;
    }

    function goToOverview() {
        $state.go("overview", { id: self.pull_request.id });
    }
}
