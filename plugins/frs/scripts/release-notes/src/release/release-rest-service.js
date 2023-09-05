export default ReleaseRestService;

ReleaseRestService.$inject = ["$http", "$q", "RestErrorService"];

function ReleaseRestService($http, $q, RestErrorService) {
    const self = this;

    Object.assign(self, {
        getAllLinkedArtifacts,
        getLinkedArtifacts,
        getReleaseLinkNatures,
        getMilestone,

        linked_artifacts_pagination_limit: 50,
        linked_artifacts_pagination_offset: 0,
    });

    function getReleaseLinkNatures(artifact_id) {
        return $http
            .get("/api/v1/artifacts/" + artifact_id + "/links", {
                cache: true,
                timeout: 10000,
            })
            .then(function (response) {
                return response.data.natures;
            })
            .catch(function (error) {
                RestErrorService.setError({
                    code: error.status,
                    message: error.data,
                });
                return $q.reject(error);
            });
    }

    function getLinkedArtifacts(uri, limit, offset) {
        return $http
            .get("/api/v1/" + uri, {
                params: {
                    limit: limit,
                    offset: offset,
                },
                cache: true,
                timeout: 20000,
            })
            .then(function (response) {
                return {
                    results: response.data.collection,
                    total: Number.parseInt(response.headers("X-PAGINATION-SIZE"), 10),
                };
            })
            .catch(function (error) {
                RestErrorService.setError({
                    code: error.status,
                    message: error.data,
                });
                return $q.reject(error);
            });
    }

    function recursiveGetLinkedArtifacts(uri, limit, offset, progress_callback) {
        return self.getLinkedArtifacts(uri, limit, offset).then(function (response) {
            var results = [].concat(response.results);

            progress_callback(results);

            if (offset + limit >= response.total) {
                return results;
            }

            return recursiveGetLinkedArtifacts(uri, limit, offset + limit, progress_callback).then(
                function (second_response) {
                    return results.concat(second_response);
                },
            );
        });
    }

    function getAllLinkedArtifacts(uri, progress_callback) {
        var limit = self.linked_artifacts_pagination_limit;
        var offset = self.linked_artifacts_pagination_offset;

        return recursiveGetLinkedArtifacts(uri, limit, offset, progress_callback);
    }

    function getMilestone(id) {
        return $http.get("/api/v1/milestones/" + id).then(function (response) {
            return response.data;
        });
    }
}
