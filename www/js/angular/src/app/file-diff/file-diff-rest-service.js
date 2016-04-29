angular
.module('tuleap.pull-request')
.service('FileDiffRestService', FileDiffRestService);

FileDiffRestService.$inject = [
    '$q',
    '$http',
    'lodash',
    'ErrorModalService'
];

function FileDiffRestService(
    $q,
    $http,
    lodash,
    ErrorModalService
) {
    var self = this;

    lodash.extend(self, {
        getUnidiff       : getUnidiff,
        postInlineComment: postInlineComment
    });

    function getUnidiff(pull_request_id, file_path) {
        return $http.get('/api/v1/pull_requests/' + pull_request_id + '/file_diff?path=' + file_path)
        .then(function(response) {
            return response.data;
        }).catch(function(response) {
            ErrorModalService.showError(response);
            return $q.reject(response);
        });
    }

    function postInlineComment(pull_request_id, file_path, unidiff_offset, content) {
        var data = {
            file_path     : file_path,
            unidiff_offset: unidiff_offset,
            content       : content
        };

        return $http.post('/api/v1/pull_requests/' + pull_request_id + '/inline-comments', data)
        .then(function(response) {
            return response.data;
        }).catch(function(response) {
            ErrorModalService.showError(response);
            return $q.reject(response);
        });
    }
}
