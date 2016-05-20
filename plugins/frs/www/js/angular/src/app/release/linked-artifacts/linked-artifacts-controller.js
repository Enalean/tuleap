angular
    .module('tuleap.frs')
    .controller('LinkedArtifactsController', LinkedArtifactsController);

LinkedArtifactsController.$inject = [
    'lodash',
    'ReleaseRestService',
    'RestErrorService',
    'SharedPropertiesService'
];

function LinkedArtifactsController(
    _,
    ReleaseRestService,
    RestErrorService,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        loading_natures: true,
        natures        : [],
        release        : SharedPropertiesService.getRelease(),

        init    : init,
        getError: RestErrorService.getError
    });

    self.init();

    function init() {
        self.loading_natures = true;

        ReleaseRestService.getReleaseLinkNatures(self.release.artifact.id)
        .then(function(natures) {
            self.natures = _(natures)
                .filter(function(nature) { return nature.label; }) // we intentionally omit links with no nature
                .map(retrieveLinkedArtifactsByNature)
                .value();
        })
        .finally(function() {
            self.loading_natures = false;
        });
    }

    function retrieveLinkedArtifactsByNature(nature) {
        nature.loading          = true;
        nature.linked_artifacts = [];

        ReleaseRestService.getAllLinkedArtifacts(nature.uri, function(batch_of_artifacts) {
            _.map(batch_of_artifacts, function(artifact) {
                nature.linked_artifacts.push(artifact);
            });
        })
        .finally(function() {
            nature.loading = false;
        });

        return nature;
    }
}
