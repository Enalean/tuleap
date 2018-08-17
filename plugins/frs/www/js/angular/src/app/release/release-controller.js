export default ReleaseController;

ReleaseController.$inject = ["ReleaseRestService", "SharedPropertiesService"];

function ReleaseController(ReleaseRestService, SharedPropertiesService) {
    const self = this;

    Object.assign(self, {
        error_no_release_artifact: false,
        project_id: SharedPropertiesService.getProjectId(),
        release: SharedPropertiesService.getRelease(),
        milestone: null,

        init
    });

    self.init();

    function init() {
        if (!doesReleaseArtifactExist()) {
            self.error_no_release_artifact = true;
            return;
        }

        ReleaseRestService.getMilestone(self.release.artifact.id).then(function(milestone) {
            self.milestone = milestone;
        });
    }

    function doesReleaseArtifactExist() {
        return self.release.artifact !== null && self.release.artifact.id !== null;
    }
}
