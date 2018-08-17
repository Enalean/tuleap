import { isThereAtLeastOneFileField, getAllFileFields } from "./file-field-detector.js";

describe("FileFieldDetector -", () => {
    describe("isThereAtLeastOneFileField() -", () => {
        it("Given a tracker with two file fields, then it will return true", () => {
            const tracker_fields = [
                { field_id: 95, type: "file" },
                { field_id: 72, type: "int" },
                { field_id: 64, type: "file" }
            ];

            expect(isThereAtLeastOneFileField(tracker_fields)).toBe(true);
        });

        it("Given a tracker with no file field, then it will return false", () => {
            const tracker_fields = [{ field_id: 62, type: "int" }];

            expect(isThereAtLeastOneFileField(tracker_fields)).toBe(false);
        });
    });

    describe("getAllFileFields() -", () => {
        it("Given a tracker with two file fields, then it will return the two file fields", () => {
            const tracker_fields = [
                { field_id: 62, type: "file" },
                { field_id: 43, type: "string" },
                { field_id: 38, type: "file" }
            ];

            const result = getAllFileFields(tracker_fields);

            expect(result).toEqual([
                { field_id: 62, type: "file" },
                { field_id: 38, type: "file" }
            ]);
        });
    });
});
