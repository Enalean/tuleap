angular
    .module('tuleap.pull-request')
    .controller('FilesController', FilesController);

FilesController.$inject = [
    'lodash',
    '$state',
    'SharedPropertiesService',
    'FilesRestService',
    'FilepathsService'
];

function FilesController(
    lodash,
    $state,
    SharedPropertiesService,
    FilesRestService,
    FilepathsService
) {
    var self = this;

    lodash.extend(self, {
        pull_request : {},
        files        : [],
        selected_file: {},
        loading_files: true,
        loadFile     : loadFile
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();
        getFiles();
    });

    function getFiles() {
        FilesRestService.getFiles(self.pull_request.id).then(function(files) {
            self.files = files;
            FilepathsService.setFilepaths(files);

            setSelectedFile();

        }).finally(function() {
            self.loading_files = false;
        });
    }

    function setSelectedFile() {
        self.selected_file = self.files[0];

        if ($state.includes('diff')) {
            self.selected_file = lodash.find(self.files, { path: $state.params.file_path });
        }

        loadFile(self.selected_file);
    }

    function loadFile(file) {
        $state.go('diff', {
            id       : self.pull_request.id,
            file_path: file.path
        });
    }
}
