export default ReviewersRestService;

ReviewersRestService.$inject = ["$http", "$q", "ErrorModalService"];

function ReviewersRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getReviewers
    });

    function getReviewers(pull_request_id) {
        return $http
            .get("/api/v1/pull_requests/" + encodeURIComponent(pull_request_id) + "/reviewers")
            .catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
