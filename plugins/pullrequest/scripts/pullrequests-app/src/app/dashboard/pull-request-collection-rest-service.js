import { noop } from "angular";

export default PullRequestCollectionRestService;

PullRequestCollectionRestService.$inject = ["$http", "$q", "ErrorModalService"];

function PullRequestCollectionRestService($http, $q, ErrorModalService) {
    const self = this;

    Object.assign(self, {
        getAllPullRequests,
        getPullRequests,
        getAllOpenPullRequests,
        getAllClosedPullRequests,

        pull_requests_pagination: {
            limit: 50,
            offset: 0,
        },
    });

    function getPullRequests(repository_id, limit, offset, status) {
        let status_param = {};

        if (typeof status === "string") {
            status_param = {
                query: {
                    status: status,
                },
            };
        }

        const params = Object.assign(
            {
                limit: limit,
                offset: offset,
            },
            status_param,
        );

        return $http
            .get("/api/v1/git/" + repository_id + "/pull_requests", {
                params: params,
                timeout: 20000,
            })
            .then(function (response) {
                return {
                    results: response.data.collection,
                    total: Number.parseInt(response.headers("X-PAGINATION-SIZE"), 10),
                };
            })
            .catch(function (error) {
                ErrorModalService.showErrorResponseMessage(error);
                return $q.reject(error);
            });
    }

    function recursiveGet(getFunction, limit, offset, callback) {
        return getFunction(offset).then(function (response) {
            const results = [].concat(response.results);

            const progress_callback = callback || noop;
            progress_callback(results);

            if (offset + limit >= response.total) {
                return results;
            }

            return recursiveGet(getFunction, limit, offset + limit, progress_callback).then(
                function (second_response) {
                    return results.concat(second_response);
                },
            );
        });
    }

    function getAllPullRequestsWithStatus(repository_id, status, callback) {
        const limit = self.pull_requests_pagination.limit;
        const offset = self.pull_requests_pagination.offset;

        const getOnePagePullRequests = (new_offset) => {
            return $q.when(self.getPullRequests(repository_id, limit, new_offset, status));
        };

        return recursiveGet(getOnePagePullRequests, limit, offset, callback);
    }

    function getAllPullRequests(repository_id, callback) {
        const status = null;

        return getAllPullRequestsWithStatus(repository_id, status, callback);
    }

    function getAllOpenPullRequests(repository_id, callback) {
        const status = "open";

        return getAllPullRequestsWithStatus(repository_id, status, callback);
    }

    function getAllClosedPullRequests(repository_id, callback) {
        const status = "closed";

        return getAllPullRequestsWithStatus(repository_id, status, callback);
    }
}
