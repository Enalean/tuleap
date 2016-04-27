angular
    .module('tuleap.pull-request')
    .service('FilesRestService', FilesRestService);

FilesRestService.$inject = [
    '$http',
    '$q',
    'lodash',
    'ErrorModalService'
];

function FilesRestService(
    $http,
    $q,
    lodash,
    ErrorModalService
) {
    var self = this;

    lodash.extend(self, {
        getFiles: getFiles
    });

    function getFiles(pull_request_id) {
        return $http.get('/api/v1/pull_requests/' + pull_request_id + '/files')
            .then(function(response) {
                return response.data;
            }).catch(function(response) {
                ErrorModalService.showError(response);
                return $q.reject(response);
            });
    }
}
