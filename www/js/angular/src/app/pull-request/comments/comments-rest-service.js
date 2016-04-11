angular
    .module('tuleap.pull-request')
    .service('CommentsRestService', CommentsRestService);

CommentsRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function CommentsRestService(
    $http,
    $q,
    lodash,
    ErrorModalService
) {
    var self = this;

    lodash.extend(self, {
        getComments: getComments,
        addComment : addComment
    });

    function getComments(pull_request_id, limit, offset) {
        return $http.get('/api/v1/pull_requests/' + pull_request_id + '/comments?limit=' + limit + '&offset=' + offset)
            .catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }

    function addComment(pull_request_id, comment) {
        var data = {
            content: comment.content,
            user_id: comment.user_id
        };

        return $http.post('/api/v1/pull_requests/' + pull_request_id + '/comments', data)
            .then(function(response) {
                return response.data;
            }).catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
