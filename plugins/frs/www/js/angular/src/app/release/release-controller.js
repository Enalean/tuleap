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
        project_id: null,
        release   : null,

        init: init
    });

    self.init();

    function init() {
        self.project_id = SharedPropertiesService.getProjectId();

        ReleaseRestService.getRelease(SharedPropertiesService.getReleaseId())
            .then(function(release) {
                self.release = release;
            });
    }
}
