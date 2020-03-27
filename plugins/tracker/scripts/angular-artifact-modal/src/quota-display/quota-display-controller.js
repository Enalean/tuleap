import { file_upload_rules } from "../tuleap-artifact-modal-fields/file-field/file-upload-rules-state.js";

export default TuleapArtifactModalQuotaDisplayController;

TuleapArtifactModalQuotaDisplayController.$inject = [];

function TuleapArtifactModalQuotaDisplayController() {
    const self = this;
    Object.assign(self, {
        file_upload_rules,
        getDiskUsagePercentage,
        isDiskUsageEmpty,
    });

    function isDiskUsageEmpty() {
        return self.file_upload_rules.disk_usage === 0;
    }

    function getDiskUsagePercentage() {
        return (self.file_upload_rules.disk_usage / self.file_upload_rules.disk_quota) * 100;
    }
}
