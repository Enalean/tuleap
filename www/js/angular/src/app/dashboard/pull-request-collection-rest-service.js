angular
    .module('tuleap.pull-request')
    .service('PullRequestCollectionRestService', PullRequestCollectionRestService);

PullRequestCollectionRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function PullRequestCollectionRestService(
    $http,
    $q,
    _,
    ErrorModalService
) {
    var self = this;

    _.extend(self, {
        getAllPullRequests: getAllPullRequests,
        getPullRequests   : getPullRequests,

        pull_requests_pagination: {
            limit : 50,
            offset: 0
        }
    });

    function getPullRequests(repository_id, limit, offset) {
        return $http.get('/api/v1/git/' + repository_id + '/pull_requests', {
            params: {
                limit : limit,
                offset: offset
            },
            timeout: 20000
        })
        .then(function(response) {
            return {
                results: response.data.collection,
                total  : _.toInteger(response.headers('X-PAGINATION-SIZE'))
            };
        })
        .catch(function(error) {
            ErrorModalService.showError(error);
            return $q.reject(error);
        });
    }

    function recursiveGetPullRequests(repository_id, limit, offset, progress_callback) {
        return self.getPullRequests(repository_id, limit, offset)
        .then(function(response) {
            var results = [].concat(response.results);

            progress_callback(results);

            if (offset + limit >= response.total) {
                return results;
            }

            return recursiveGetPullRequests(
                repository_id,
                limit,
                offset + limit,
                progress_callback
            ).then(function(second_response) {
                return results.concat(second_response);
            });
        });
    }

    function getAllPullRequests(repository_id, progress_callback) {
        var limit  = self.pull_requests_pagination.limit;
        var offset = self.pull_requests_pagination.offset;

        return recursiveGetPullRequests(
            repository_id,
            limit,
            offset,
            progress_callback
        );
    }
}
