export default PullRequestRestService;

PullRequestRestService.$inject = ["$http", "$q", "ErrorModalService"];

function PullRequestRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getPullRequest,
    });

    function getPullRequest(pull_request_id) {
        return $http
            .get("/api/v1/pull_requests/" + pull_request_id, {
                timeout: 20000,
            })
            .then(function (response) {
                return response.data;
            })
            .catch(function (response) {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }
}
