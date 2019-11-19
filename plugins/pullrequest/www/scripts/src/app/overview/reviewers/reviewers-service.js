export default ReviewersService;

ReviewersService.$inject = ["ReviewersRestService"];

function ReviewersService(ReviewersRestService) {
    const self = this;

    Object.assign(self, {
        getReviewers
    });

    function getReviewers(pull_request) {
        let reviewers = [];

        return ReviewersRestService.getReviewers(pull_request.id).then(response => {
            reviewers = response.data.users;

            return reviewers;
        });
    }
}
