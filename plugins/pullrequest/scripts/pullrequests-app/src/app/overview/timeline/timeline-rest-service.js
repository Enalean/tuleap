export default TimelineRestService;

TimelineRestService.$inject = ["$http", "$q", "ErrorModalService"];

function TimelineRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getTimeline,
        addComment,
    });

    function getTimeline(pull_request_id, limit, offset) {
        return $http
            .get(
                "/api/v1/pull_requests/" +
                    pull_request_id +
                    "/timeline?limit=" +
                    limit +
                    "&offset=" +
                    offset
            )
            .catch(function (response) {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }

    function addComment(pull_request_id, comment) {
        var data = {
            content: comment.content,
            user_id: comment.user_id,
        };

        return $http
            .post("/api/v1/pull_requests/" + pull_request_id + "/comments", data)
            .then(function (response) {
                return response.data;
            })
            .catch(function (response) {
                ErrorModalService.showErrorResponseMessage(response);
                return $q.reject(response);
            });
    }
}
