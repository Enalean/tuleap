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
        project_id: null,
        release   : null,

        init: init
    });

    self.init();

    function init() {
        self.project_id = SharedPropertiesService.getProjectId();
        self.release    = SharedPropertiesService.getRelease();
    }
}
