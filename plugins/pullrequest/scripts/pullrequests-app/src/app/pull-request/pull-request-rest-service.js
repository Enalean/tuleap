export default PullRequestRestService;

PullRequestRestService.$inject = ["$http", "$q", "ErrorModalService"];

function PullRequestRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getPullRequest,
        updateStatus,
        updateTitleAndDescription,
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

    function updateStatus(pull_request_id, status) {
        return $http
            .patch("/api/v1/pull_requests/" + pull_request_id, { status: status })
            .catch(function (response) {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }

    function updateTitleAndDescription(pull_request_id, new_title, new_description) {
        return $http
            .patch("/api/v1/pull_requests/" + pull_request_id, {
                title: new_title,
                description: new_description,
            })
            .catch(function (response) {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }
}
