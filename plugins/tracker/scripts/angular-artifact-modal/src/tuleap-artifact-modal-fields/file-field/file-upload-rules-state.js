import { isThereAtLeastOneFileField } from "./file-field-detector.js";
import { getFileUploadRules } from "../../rest/rest-service.js";

const file_upload_rules = {
    // All units are in bytes
    disk_quota: 0,
    disk_usage: 0,
    max_chunk_size: 0,
};

export { updateFileUploadRulesWhenNeeded, file_upload_rules };

function updateFileUploadRulesWhenNeeded(field_values) {
    if (isThereAtLeastOneFileField(field_values)) {
        return getFileUploadRules().then((data) => {
            Object.assign(file_upload_rules, data);
        });
    }

    return Promise.resolve();
}
