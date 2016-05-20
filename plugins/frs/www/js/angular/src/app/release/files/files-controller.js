angular
    .module('tuleap.frs')
    .controller('FilesController', FilesController);

FilesController.$inject = [
    'lodash',
    'SharedPropertiesService'
];

function FilesController(
    _,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        release: SharedPropertiesService.getRelease()
    });
}
