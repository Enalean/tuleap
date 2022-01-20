/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import { updateFileUploadRulesWhenNeeded, file_upload_rules } from "./file-upload-rules-state.js";
import * as file_field_detector from "./file-field-detector";
import * as rest_service from "../../rest/rest-service";

describe("FileUploadRulesUpdater() -", () => {
    let isThereAtLeastOneFileField, getFileUploadRules;

    beforeEach(() => {
        isThereAtLeastOneFileField = jest.spyOn(file_field_detector, "isThereAtLeastOneFileField");
        getFileUploadRules = jest.spyOn(rest_service, "getFileUploadRules");
    });

    describe("updateFileUploadRulesWhenNeeded() -", () => {
        it("Given there was one file field, then the File upload rules will be queried and stored and the query promise will be returned", async () => {
            const field_values = {
                22: { field_id: 22, type: "file" },
            };
            isThereAtLeastOneFileField.mockReturnValue(true);
            const rules = {
                disk_quota: 64,
                disk_usage: 57,
                max_chunk_size: 96,
            };
            getFileUploadRules.mockReturnValue(Promise.resolve(rules));

            await updateFileUploadRulesWhenNeeded(field_values);

            expect(isThereAtLeastOneFileField).toHaveBeenCalledWith(field_values);
            expect(getFileUploadRules).toHaveBeenCalled();
            expect(file_upload_rules).toEqual(rules);
        });

        it("Given there was no file filed, then the File upload rules won't be queried and a promise will be resolved", async () => {
            const field_values = {
                27: { field_id: 27, type: "string" },
            };
            isThereAtLeastOneFileField.mockReturnValue(false);

            await updateFileUploadRulesWhenNeeded(field_values);

            expect(getFileUploadRules).not.toHaveBeenCalled();
        });
    });
});
