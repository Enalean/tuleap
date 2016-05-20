angular
    .module('tuleap.pull-request')
    .controller('FilesController', FilesController);

FilesController.$inject = [
    'lodash',
    'SharedPropertiesService',
    'FilesRestService',
    'FilepathsService'
];

function FilesController(
    lodash,
    SharedPropertiesService,
    FilesRestService,
    FilepathsService
) {
    var self = this;

    lodash.extend(self, {
        pull_request : {},
        files        : [],
        loading_files: true
    });

    SharedPropertiesService.whenReady().then(function() {
        self.pull_request = SharedPropertiesService.getPullRequest();
        getFiles();
    });

    function getFiles() {
        FilesRestService.getFiles(self.pull_request.id).then(function(files) {
            self.files = files;
            FilepathsService.setFilepaths(files);
        }).finally(function() {
            self.loading_files = false;
        });
    }
}
