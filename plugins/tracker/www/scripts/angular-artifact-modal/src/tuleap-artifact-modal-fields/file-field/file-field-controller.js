import { isUndefined, remove } from "lodash";

export default FileFieldController;

FileFieldController.$inject = [];

function FileFieldController() {
    const self = this;
    Object.assign(self, {
        addTemporaryFileInput,
        resetTemporaryFileInput,
        toggleMarkedForRemoval
    });

    function addTemporaryFileInput() {
        self.value_model.temporary_files.push({});
    }

    function resetTemporaryFileInput(index) {
        if (isUndefined(self.value_model.temporary_files[index])) {
            return;
        }

        self.value_model.temporary_files[index] = {
            file: {},
            description: ""
        };
    }

    function toggleMarkedForRemoval(file, index) {
        if (file.marked_for_removal) {
            return unmarkFileForRemoval(file, index);
        }

        return markFileForRemoval(file);
    }

    function markFileForRemoval(file_to_mark) {
        remove(self.value_model.value, function(id) {
            return id === file_to_mark.id;
        });

        file_to_mark.marked_for_removal = true;
    }

    function unmarkFileForRemoval(file_to_unmark, index) {
        self.value_model.value.splice(index, 0, file_to_unmark.id);

        file_to_unmark.marked_for_removal = false;
    }
}
