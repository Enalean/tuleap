export default CommitsRestService;

CommitsRestService.$inject = ["$http", "$q", "ErrorModalService"];

function CommitsRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, { getPaginatedCommits });

    function getCommits(pull_request_id, limit, offset) {
        return $http
            .get(`/api/v1/pull_requests/${pull_request_id}/commits?limit=${limit}&offset=${offset}`)
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }

    function getPaginatedCommits(pull_request_id, limit, offset, callback) {
        return getCommits(pull_request_id, limit, offset).then((response) => {
            callback(response);

            const total = response.headers()["x-pagination-size"];

            if (limit + offset < total) {
                return getPaginatedCommits(pull_request_id, limit, offset + limit, callback);
            }
        });
    }
}
