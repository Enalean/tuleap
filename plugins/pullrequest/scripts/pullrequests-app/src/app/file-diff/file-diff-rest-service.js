export default FileDiffRestService;

FileDiffRestService.$inject = ["$q", "$http", "ErrorModalService"];

function FileDiffRestService($q, $http, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getUnidiff,
    });

    function getUnidiff(pull_request_id, file_path) {
        return $http
            .get(
                "/api/v1/pull_requests/" +
                    encodeURIComponent(pull_request_id) +
                    "/file_diff?path=" +
                    encodeURIComponent(file_path)
            )
            .then(({ data }) => data)
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }
}
