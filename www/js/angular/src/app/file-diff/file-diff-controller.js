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
    lodash,
    SharedPropertiesService
) {
    var self = this;

    lodash.extend(self, {
        file_path   : $state.params.file_path,
        pull_request: SharedPropertiesService.getPullRequest()
    });

}
