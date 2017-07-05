angular
    .module('tuleap-artifact-modal-file-field')
    .value('TuleapArtifactModalFileUploadRules', {
        // All units are in bytes
        disk_quota    : 0,
        disk_usage    : 0,
        max_chunk_size: 0
    });
