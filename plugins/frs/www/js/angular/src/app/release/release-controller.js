angular
    .module('tuleap.frs')
    .controller('ReleaseController', ReleaseController);

ReleaseController.$inject = [
    'lodash',
    'ReleaseRestService',
    'SharedPropertiesService'
];

function ReleaseController(
    _,
    ReleaseRestService,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        error_no_release_artifact: false,
        project_id               : SharedPropertiesService.getProjectId(),
        release                  : SharedPropertiesService.getRelease(),
        milestone                : null,

        init: init
    });

    self.init();

    function init() {
        if (!_.has(self.release, 'artifact.id')) {
            self.error_no_release_artifact = true;
            return;
        }

        ReleaseRestService.getMilestone(self.release.artifact.id)
            .then(function(milestone) {
                self.milestone = milestone;
            });
    }
}
