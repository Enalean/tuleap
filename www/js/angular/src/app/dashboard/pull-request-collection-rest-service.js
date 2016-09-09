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
        getAllPullRequests      : getAllPullRequests,
        getPullRequests         : getPullRequests,
        getAllOpenPullRequests  : getAllOpenPullRequests,
        getAllClosedPullRequests: getAllClosedPullRequests,

        pull_requests_pagination: {
            limit : 50,
            offset: 0
        }
    });

    function getPullRequests(repository_id, limit, offset, status) {
        var status_param = {};

        if (_.isString(status)) {
            status_param = {
                query: {
                    status: status
                }
            };
        }

        var params = _.extend({
            limit : limit,
            offset: offset
        }, status_param);

        return $http.get('/api/v1/git/' + repository_id + '/pull_requests', {
            params : params,
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

    function recursiveGet(getFunction, limit, offset, callback) {
        return getFunction(limit, offset)
        .then(function(response) {
            var results = [].concat(response.results);

            var progress_callback = callback || angular.noop;
            progress_callback(results);

            if (offset + limit >= response.total) {
                return results;
            }

            return recursiveGet(
                getFunction,
                limit,
                offset + limit,
                progress_callback
            ).then(function(second_response) {
                return results.concat(second_response);
            });
        });
    }

    function getAllPullRequestsWithStatus(repository_id, status, callback) {
        var limit  = self.pull_requests_pagination.limit;
        var offset = self.pull_requests_pagination.offset;

        var getOnePagePullRequests =  _.partial(
            self.getPullRequests,
            repository_id,
            _.partial.placeholder,
            _.partial.placeholder,
            status
        );

        return recursiveGet(
            getOnePagePullRequests,
            limit,
            offset,
            callback
        );
    }

    function getAllPullRequests(repository_id, callback) {
        var status = null;

        return getAllPullRequestsWithStatus(repository_id, status, callback);
    }

    function getAllOpenPullRequests(repository_id, callback) {
        var status = 'open';

        return getAllPullRequestsWithStatus(repository_id, status, callback);
    }

    function getAllClosedPullRequests(repository_id, callback) {
        var status = 'closed';

        return getAllPullRequestsWithStatus(repository_id, status, callback);
    }
}
