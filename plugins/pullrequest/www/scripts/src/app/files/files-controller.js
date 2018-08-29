import { dropdown } from "tlp";

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
        file_selector: null,
        selected_file: {},
        loading_files: true,
        loadFile,
        initFileDropdown,
        isFileSelected
    });

    SharedPropertiesService.whenReady().then(() => {
        self.pull_request = SharedPropertiesService.getPullRequest();
        getFiles();
        initFileDropdown();
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
            self.selected_file = self.files.find(file => file.path === $state.params.file_path);
        }

        loadFile(self.selected_file);
    }

    function loadFile(file) {
        self.selected_file = file;

        $state.go("diff", {
            id: self.pull_request.id,
            file_path: file.path
        });

        self.file_selector.hide();
    }

    function initFileDropdown() {
        self.file_selector = dropdown(document.getElementById("file-switcher-dropdown-button"), {
            keyboard: false
        });
    }

    function isFileSelected(file) {
        return self.selected_file.path === file.path;
    }
}
