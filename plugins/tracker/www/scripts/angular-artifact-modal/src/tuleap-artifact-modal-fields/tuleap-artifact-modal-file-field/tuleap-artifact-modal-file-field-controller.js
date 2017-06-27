angular
    .module('tuleap-artifact-modal-file-field')
    .controller('TuleapArtifactModalFileFieldController', TuleapArtifactModalFileFieldController);

TuleapArtifactModalFileFieldController.$inject = [

];

function TuleapArtifactModalFileFieldController(

) {
    var self = this;
    _.extend(self, {
        addTemporaryFileInput  : addTemporaryFileInput,
        onFileLoaded           : onFileLoaded,
        resetTemporaryFileInput: resetTemporaryFileInput,
        toggleMarkedForRemoval : toggleMarkedForRemoval
    });

    function addTemporaryFileInput() {
        self.value_model.temporary_files.push({});
    }

    function resetTemporaryFileInput(index) {
        if (_.isUndefined(self.value_model.temporary_files[index])) {
            return;
        }

        self.value_model.temporary_files[index] = {
            file: {},
            description: ""
        };
    }

    function onFileLoaded(
        ProgressEvent,
        FileReader,
        File,
        FileList,
        FileModels,
        FileModel
    ) {
        if (FileModel.filetype === "") {
            FileModel.filetype = "application/octet-stream";
        }
    }

    function toggleMarkedForRemoval(file, index) {
        if (file.marked_for_removal) {
            return unmarkFileForRemoval(file, index);
        } else {
            return markFileForRemoval(file);
        }
    }

    function markFileForRemoval(file_to_mark) {
        _.remove(self.value_model.value, function(id) {
            return id === file_to_mark.id;
        });

        file_to_mark.marked_for_removal = true;
    }

    function unmarkFileForRemoval(file_to_unmark, index) {
        self.value_model.value.splice(index, 0, file_to_unmark.id);

        file_to_unmark.marked_for_removal = false;
    }
}
