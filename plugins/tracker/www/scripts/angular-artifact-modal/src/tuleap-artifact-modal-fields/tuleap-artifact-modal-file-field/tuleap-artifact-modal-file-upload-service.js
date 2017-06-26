angular
    .module('tuleap-artifact-modal-file-field')
    .service('TuleapArtifactModalFileUploadService', TuleapArtifactModalFileUploadService);

TuleapArtifactModalFileUploadService.$inject = [
    '$q',
    'TuleapArtifactModalRestService',
    'TuleapArtifactModalFileUploadRules'
];

function TuleapArtifactModalFileUploadService(
    $q,
    TuleapArtifactModalRestService,
    TuleapArtifactModalFileUploadRules
) {
    var self = this;
    _.extend(self, {
        uploadAllTemporaryFiles: uploadAllTemporaryFiles,
        uploadTemporaryFile    : uploadTemporaryFile
    });
    var file_upload_rules = TuleapArtifactModalFileUploadRules;

    function uploadAllTemporaryFiles(temporary_files) {
        var promises = _.map(temporary_files, function(file) {
            return self.uploadTemporaryFile(file);
        });

        return $q.all(promises);
    }

    function uploadTemporaryFile(temporary_file) {
        if (! _.has(temporary_file, 'file') || ! _.has(temporary_file.file, 'base64')) {
            return $q.when();
        }

        temporary_file.file.chunks = splitIntoChunks(temporary_file.file.base64);

        var promise = TuleapArtifactModalRestService.uploadTemporaryFile(
            temporary_file.file,
            temporary_file.description
        ).then(function(temporary_file_id) {
            return uploadAllAdditionalChunks(
                temporary_file_id,
                temporary_file.file.chunks,
                2
            );
        });

        return promise;
    }

    function splitIntoChunks(base64_data) {
        var remaining_data = base64_data;
        var chunks         = [];

        do {
            chunks.push(remaining_data.substring(0, file_upload_rules.max_chunk_size));
        } while ((remaining_data = remaining_data.substring(file_upload_rules.max_chunk_size, remaining_data.length)) !== "");

        return chunks;
    }

    function uploadAllAdditionalChunks(temporary_file_id, chunks, chunk_offset) {
        if (chunks.length < chunk_offset) {
            return $q.when(temporary_file_id);
        }

        var promise = TuleapArtifactModalRestService.uploadAdditionalChunk(
            temporary_file_id,
            chunks[chunk_offset - 1],
            chunk_offset
        ).then(function() {
            return uploadAllAdditionalChunks(
                temporary_file_id,
                chunks,
                chunk_offset + 1
            );
        });

        return promise;
    }
}
