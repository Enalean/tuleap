export default ReviewersController;

ReviewersController.$inject = ["SharedPropertiesService", "ReviewersService", "PullRequestService"];

function ReviewersController(SharedPropertiesService, ReviewersService, PullRequestService) {
    const self = this;

    Object.assign(self, {
        pull_request: {},
        reviewers: [],
        loading_reviewers: true,
        hasEditRight: hasEditRight,
        $onInit: init
    });

    function init() {
        SharedPropertiesService.whenReady().then(function() {
            self.pull_request = SharedPropertiesService.getPullRequest();

            ReviewersService.getReviewers(self.pull_request)
                .then(reviewers => {
                    self.reviewers = reviewers;
                })
                .finally(() => {
                    self.loading_reviewers = false;
                });
        });
    }

    function hasEditRight() {
        return (
            self.pull_request.user_can_merge &&
            !PullRequestService.isPullRequestClosed(self.pull_request)
        );
    }
}
