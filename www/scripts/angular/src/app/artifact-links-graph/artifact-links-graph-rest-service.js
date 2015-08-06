angular
    .module('tuleap.artifact-links-graph')
    .service('ArtifactLinksGraphRestService', ArtifactLinksGraphRestService);

ArtifactLinksGraphRestService.$inject = ['Restangular'];

function ArtifactLinksGraphRestService(Restangular) {
    var rest = Restangular.withConfig(function(RestangularConfigurer) {
        RestangularConfigurer.setFullResponse(true);
        RestangularConfigurer.setBaseUrl('/api/v1');
    });

    return {
        getArtifact: getArtifact,
        getTracker: getTracker
    };

    function getArtifact(artifact_id) {
        return rest
            .one('artifacts', artifact_id)
            .get()
            .then(function(response) {
                return response.data;
            });
    }

    function getTracker(tracker_id) {
        return rest
            .one('trackers', tracker_id)
            .get()
            .then(function(response) {
                return response.data;
            });
    }

}