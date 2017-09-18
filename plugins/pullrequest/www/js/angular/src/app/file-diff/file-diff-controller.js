angular
    .module('tuleap.pull-request')
    .controller('FileDiffController', FileDiffController);

FileDiffController.$inject = [
    '$state',
    'lodash',
    'SharedPropertiesService'
];

function FileDiffController(
    $state,
    _,
    SharedPropertiesService
) {
    var self = this;

    _.extend(self, {
        file_path   : $state.params.file_path,
        pull_request: SharedPropertiesService.getPullRequest()
    });
}
