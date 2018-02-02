import {
    uploadTemporaryFile as uploadFile,
    uploadAdditionalChunk
} from '../../rest/rest-service.js';

export default FileUploadService;

FileUploadService.$inject = [
    '$q',
    'TuleapArtifactModalFileUploadRules'
];

function FileUploadService(
    $q,
    TuleapArtifactModalFileUploadRules
) {
    const self = this;
    Object.assign(self, {
        uploadAllTemporaryFiles,
        uploadTemporaryFile
    });
    var file_upload_rules = TuleapArtifactModalFileUploadRules;

    function uploadAllTemporaryFiles(temporary_files) {
        var promises = temporary_files.map(function(file) {
            return self.uploadTemporaryFile(file);
        });

        return $q.all(promises);
    }

    function uploadTemporaryFile(temporary_file) {
        if (! temporary_file.hasOwnProperty('file') || ! temporary_file.file.hasOwnProperty('base64')) {
            return $q.when();
        }

        temporary_file.file.chunks = splitIntoChunks(temporary_file.file.base64);

        var promise = $q.when(uploadFile(
            temporary_file.file,
            temporary_file.description
        )).then(function(temporary_file_id) {
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

        var promise = $q.when(uploadAdditionalChunk(
            temporary_file_id,
            chunks[chunk_offset - 1],
            chunk_offset
        )).then(function() {
            return uploadAllAdditionalChunks(
                temporary_file_id,
                chunks,
                chunk_offset + 1
            );
        });

        return promise;
    }
}
