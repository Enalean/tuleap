import { dropdown } from "tlp";

export default FilesController;

FilesController.$inject = [
    "$state",
    "SharedPropertiesService",
    "FilesRestService",
    "FilepathsService",
    "UserRestService"
];

function FilesController(
    $state,
    SharedPropertiesService,
    FilesRestService,
    FilepathsService,
    UserRestService
) {
    const self = this;

    const USER_DIFF_DISPLAY_MODE_PREFERENCE = "pull_requests_diff_display_mode";
    const SIDE_BY_SIDE_DIFF = "side_by_side";
    const UNIFIED_DIFF = "unified";

    Object.assign(self, {
        pull_request: {},
        files: [],
        file_selector: null,
        selected_file: {},
        loading_files: true,
        diff_display_mode: null,
        side_by_side_diff: SIDE_BY_SIDE_DIFF,
        unified_diff: UNIFIED_DIFF,
        loadFile,
        initFileDropdown,
        isCurrentDisplayMode,
        isFileSelected,
        switchDiffDisplayMode,
        $onInit: init
    });

    function init() {
        SharedPropertiesService.whenReady().then(() => {
            self.pull_request = SharedPropertiesService.getPullRequest();
            getFiles();
            initFileDropdown();

            const user_id = SharedPropertiesService.getUserId();

            if (!user_id) {
                self.diff_display_mode = UNIFIED_DIFF;

                return;
            }

            UserRestService.getPreference(user_id, USER_DIFF_DISPLAY_MODE_PREFERENCE).then(
                ({ value }) => {
                    if (!value) {
                        self.diff_display_mode = UNIFIED_DIFF;

                        return;
                    }

                    self.diff_display_mode = value;
                }
            );
        });
    }

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

    function isCurrentDisplayMode(display_mode) {
        return self.diff_display_mode === display_mode;
    }

    function switchDiffDisplayMode(new_display_mode) {
        const user_id = SharedPropertiesService.getUserId();

        self.diff_display_mode = new_display_mode;

        if (!user_id) {
            return;
        }

        UserRestService.setPreference(user_id, USER_DIFF_DISPLAY_MODE_PREFERENCE, new_display_mode);
    }
}
