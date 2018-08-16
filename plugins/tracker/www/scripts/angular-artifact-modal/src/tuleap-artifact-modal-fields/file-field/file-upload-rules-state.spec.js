import { updateFileUploadRulesWhenNeeded, file_upload_rules } from "./file-upload-rules-state.js";
import {
    rewire$isThereAtLeastOneFileField,
    restore as restoreDetector
} from "./file-field-detector.js";
import { rewire$getFileUploadRules, restore as restoreRest } from "../../rest/rest-service.js";

describe("FileUploadRulesUpdater() -", () => {
    let isThereAtLeastOneFileField, getFileUploadRules;

    beforeEach(() => {
        isThereAtLeastOneFileField = jasmine.createSpy("isThereAtLeastOneFileField");
        rewire$isThereAtLeastOneFileField(isThereAtLeastOneFileField);
        getFileUploadRules = jasmine.createSpy("getFileUploadRules");
        rewire$getFileUploadRules(getFileUploadRules);
    });

    afterEach(() => {
        restoreDetector();
        restoreRest();
    });

    describe("updateFileUploadRulesWhenNeeded() -", () => {
        it("Given there was one file field, then the File upload rules will be queried and stored and the query promise will be returned", async () => {
            const field_values = {
                22: { field_id: 22, type: "file" }
            };
            isThereAtLeastOneFileField.and.returnValue(true);
            const rules = {
                disk_quota: 64,
                disk_usage: 57,
                max_chunk_size: 96
            };
            getFileUploadRules.and.returnValue(Promise.resolve(rules));

            await updateFileUploadRulesWhenNeeded(field_values);

            expect(isThereAtLeastOneFileField).toHaveBeenCalledWith(field_values);
            expect(getFileUploadRules).toHaveBeenCalled();
            expect(file_upload_rules).toEqual(rules);
        });

        it("Given there was no file filed, then the File upload rules won't be queried and a promise will be resolved", async () => {
            const field_values = {
                27: { field_id: 27, type: "string" }
            };
            isThereAtLeastOneFileField.and.returnValue(false);

            await updateFileUploadRulesWhenNeeded(field_values);

            expect(getFileUploadRules).not.toHaveBeenCalled();
        });
    });
});
