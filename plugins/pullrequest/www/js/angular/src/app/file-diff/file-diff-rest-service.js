angular
    .module('tuleap.pull-request')
    .service('FileDiffRestService', FileDiffRestService);

FileDiffRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function FileDiffRestService(
    $http,
    $q,
    lodash,
    ErrorModalService
) {
    var self = this;

    lodash.extend(self, {
        getFileContent: getFileContent
    });

    function getFileContent(pull_request_id, file_path) {
        return $http.get('/api/v1/pull_requests/' + pull_request_id + '/file_content?path=' + file_path)
            .then(function(response) {
                return response.data;

            }).catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
