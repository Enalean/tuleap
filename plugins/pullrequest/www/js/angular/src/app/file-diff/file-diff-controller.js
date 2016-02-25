angular
    .module('tuleap.pull-request')
    .controller('FileDiffController', FileDiffController);

FileDiffController.$inject = [
    '$scope',
    '$state',
    'lodash',
    'FileDiffRestService',
    'SharedPropertiesService'
];

function FileDiffController(
    $scope,
    $state,
    lodash,
    FileDiffRestService,
    SharedPropertiesService
) {
    var self = this;

    lodash.extend(self, {
        file_path     : $state.params.file_path,
        pull_request  : SharedPropertiesService.getPullRequest(),
        editor_options: {
            lineWrapping     : true,
            lineNumbers      : true,
            collapseIdentical: 4,
            revertButtons    : false,
            origLeft         : ''
        }
    });

    getFileContent();

    function getFileContent() {
        FileDiffRestService.getFileContent(self.pull_request.id, self.file_path).then(function(file) {
            var previous_content = file.old_content === null ? '' : file.old_content;
            var new_content      = file.new_content === null ? '' : file.new_content;

            $scope.$broadcast('CodeMirror', function(code_mirror) {
                code_mirror.leftOriginal().setValue(previous_content);
                code_mirror.editor().setValue(new_content);
            });
        });
    }
}
