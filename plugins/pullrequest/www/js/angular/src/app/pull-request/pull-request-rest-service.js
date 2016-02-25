angular
    .module('tuleap.pull-request')
    .service('PullRequestRestService', PullRequestRestService);

PullRequestRestService.$inject = [
    '$http',
    'lodash',
    'ErrorModalService'
];

function PullRequestRestService(
    $http,
    lodash,
    ErrorModalService
) {
    var self = this;

    lodash.extend(self, {
        getPullRequest: getPullRequest,
        merge         : merge,
        abandon       : abandon
    });

    function getPullRequest(pull_request_id) {
        return $http.get('/api/v1/pull_requests/' + pull_request_id)
            .then(function(response) {
                return response.data;

            }).catch(function(response) {
                ErrorModalService.showError(response);
            });
    }

    function merge(pull_request_id) {
        // return $http.post({
        //     url: '/api/v1/pull_requests/' + pull_request_id + '/merge'
        // });
    }

    function abandon(pull_request_id) {
        // return $http.post({
        //     url: '/api/v1/pull_requests/' + pull_request_id + '/abandon'
        // });
    }
}
