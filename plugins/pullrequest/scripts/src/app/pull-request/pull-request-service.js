export default PullRequestService;

PullRequestService.$inject = ["PullRequestRestService"];

function PullRequestService(PullRequestRestService) {
    const self = this;

    Object.assign(self, {
        valid_status_keys: {
            review: "review",
            merge: "merge",
            abandon: "abandon",
        },

        abandon,
        isPullRequestClosed,
        merge,
        updateTitleAndDescription,
    });

    function merge(pull_request) {
        return PullRequestRestService.updateStatus(
            pull_request.id,
            self.valid_status_keys.merge
        ).then(function () {
            pull_request.status = self.valid_status_keys.merge;
        });
    }

    function abandon(pull_request) {
        return PullRequestRestService.updateStatus(
            pull_request.id,
            self.valid_status_keys.abandon
        ).then(function () {
            pull_request.status = self.valid_status_keys.abandon;
        });
    }

    function updateTitleAndDescription(pull_request, new_title, new_description) {
        return PullRequestRestService.updateTitleAndDescription(
            pull_request.id,
            new_title,
            new_description
        ).then(function (response) {
            pull_request.title = response.data.title;
            pull_request.description = response.data.description;
        });
    }

    function isPullRequestClosed(pull_request) {
        var closed_status = [self.valid_status_keys.merge, self.valid_status_keys.abandon];

        return closed_status.includes(pull_request.status);
    }
}
