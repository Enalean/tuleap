export default FilesRestService;

FilesRestService.$inject = ["$http", "$q", "ErrorModalService"];

function FilesRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, { getFiles });

    function getFiles(pull_request_id) {
        return $http
            .get("/api/v1/pull_requests/" + pull_request_id + "/files")
            .then(({ data }) => data)
            .catch((response) => {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }
}
