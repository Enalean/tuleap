angular
    .module('tuleap.frs')
    .controller('ReleaseController', ReleaseController);

ReleaseController.$inject = [
    'lodash',
    'SharedPropertiesService'
];

function ReleaseController(
    _,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        error_no_release_artifact: false,
        project_id               : SharedPropertiesService.getProjectId(),
        release                  : SharedPropertiesService.getRelease(),

        init: init
    });

    self.init();

    function init() {
        if (! _.has(self.release, 'artifact.id')) {
            self.error_no_release_artifact = true;
            return;
        }
    }
}
