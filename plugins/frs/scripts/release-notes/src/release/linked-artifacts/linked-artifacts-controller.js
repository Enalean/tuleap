export default LinkedArtifactsController;

LinkedArtifactsController.$inject = [
    "ReleaseRestService",
    "RestErrorService",
    "SharedPropertiesService",
];

function LinkedArtifactsController(ReleaseRestService, RestErrorService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        loading_natures: true,
        natures: [],
        release: SharedPropertiesService.getRelease(),

        init,
        getError: RestErrorService.getError,
    });

    self.init();

    function init() {
        self.loading_natures = true;

        ReleaseRestService.getReleaseLinkNatures(self.release.artifact.id)
            .then(function (natures) {
                self.natures = natures
                    .filter(({ label }) => label) // we intentionally omit links with no nature
                    .map(retrieveLinkedArtifactsByNature);
            })
            .finally(function () {
                self.loading_natures = false;
            });
    }

    function retrieveLinkedArtifactsByNature(nature) {
        nature.loading = true;
        nature.linked_artifacts = [];

        ReleaseRestService.getAllLinkedArtifacts(nature.uri, function (batch_of_artifacts) {
            batch_of_artifacts.map((artifact) => {
                nature.linked_artifacts.push(artifact);
            });
        }).finally(function () {
            nature.loading = false;
        });

        return nature;
    }
}
