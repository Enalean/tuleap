export default FileDiffRestService;

FileDiffRestService.$inject = ["$q", "$http", "ErrorModalService"];

function FileDiffRestService($q, $http, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getUnidiff,
        postInlineComment,
    });

    function getUnidiff(pull_request_id, file_path) {
        return $http
            .get("/api/v1/pull_requests/" + pull_request_id + "/file_diff?path=" + file_path)
            .then(({ data }) => data)
            .catch((response) => {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }

    function postInlineComment(pull_request_id, file_path, unidiff_offset, content, position) {
        const data = {
            file_path,
            unidiff_offset,
            content,
            position,
        };

        return $http
            .post("/api/v1/pull_requests/" + pull_request_id + "/inline-comments", data)
            .then(({ data }) => data)
            .catch((response) => {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
