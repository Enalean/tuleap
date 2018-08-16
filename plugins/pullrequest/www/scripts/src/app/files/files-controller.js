export default FilesController;

FilesController.$inject = [
    "$state",
    "SharedPropertiesService",
    "FilesRestService",
    "FilepathsService"
];

function FilesController($state, SharedPropertiesService, FilesRestService, FilepathsService) {
    const self = this;

    Object.assign(self, {
        pull_request: {},
        files: [],
        selected_file: {},
        loading_files: true,
        loadFile
    });

    SharedPropertiesService.whenReady().then(() => {
        self.pull_request = SharedPropertiesService.getPullRequest();
        getFiles();
    });

    function getFiles() {
        FilesRestService.getFiles(self.pull_request.id)
            .then(files => {
                self.files = files;
                FilepathsService.setFilepaths(files);

                setSelectedFile();
            })
            .finally(() => {
                self.loading_files = false;
            });
    }

    function setSelectedFile() {
        self.selected_file = self.files[0];

        if ($state.includes("diff")) {
            self.selected_file = self.files.find({ path: $state.params.file_path });
        }

        loadFile(self.selected_file);
    }

    function loadFile(file) {
        $state.go("diff", {
            id: self.pull_request.id,
            file_path: file.path
        });
    }
}
