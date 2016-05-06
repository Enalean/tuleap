angular
    .module('tuleap.frs')
    .service('ReleaseRestService', ReleaseRestService);

ReleaseRestService.$inject = [
    '$http',
    'lodash'
];

function ReleaseRestService(
    $http,
    _
) {
    var self = this;

    _.extend(self, {
        getRelease: getRelease
    });

    function getRelease(release_id) {
        return $http.get('/api/v1/frs_release/' + release_id)
            .then(function(response) {
                return response.data;
            });
    }
}
