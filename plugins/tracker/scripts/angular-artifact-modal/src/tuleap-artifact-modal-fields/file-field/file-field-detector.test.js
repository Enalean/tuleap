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

import {
    isThereAtLeastOneFileField,
    getAllFileFields,
    getFirstFileField,
} from "./file-field-detector.js";
import * as disabled_field_detector from "../disabled-field-detector.js";

describe("file-field-detector", () => {
    let isDisabled;
    beforeEach(() => {
        isDisabled = jest.spyOn(disabled_field_detector, "isDisabled");
    });

    describe("isThereAtLeastOneFileField()", () => {
        it(`Given a tracker with two enabled file fields, then it returns true`, () => {
            const tracker_fields = [
                { field_id: 95, type: "file" },
                { field_id: 72, type: "int" },
                { field_id: 64, type: "file" },
            ];
            isDisabled.mockReturnValue(false);

            expect(isThereAtLeastOneFileField(tracker_fields)).toBe(true);
        });

        it(`Given a tracker with a disabled file field, then it returns false`, () => {
            const tracker_fields = [{ field_id: 95, type: "file" }];
            isDisabled.mockReturnValue(true);

            expect(isThereAtLeastOneFileField(tracker_fields)).toBe(false);
        });

        it("Given a tracker with no file field, then it will return false", () => {
            const tracker_fields = [{ field_id: 62, type: "int" }];
            isDisabled.mockReturnValue(false);

            expect(isThereAtLeastOneFileField(tracker_fields)).toBe(false);
        });
    });

    describe("getAllFileFields()", () => {
        it(`Given a tracker with two enabled file fields,
            then it will return the two file fields`, () => {
            const tracker_fields = [
                { field_id: 62, type: "file" },
                { field_id: 43, type: "string" },
                { field_id: 38, type: "file" },
            ];
            isDisabled.mockReturnValue(false);

            const result = getAllFileFields(tracker_fields);

            expect(result).toEqual([
                { field_id: 62, type: "file" },
                { field_id: 38, type: "file" },
            ]);
        });

        it(`Given a tracker with one enabled file field
            and one disabled file field,
            then it will return only the enabled field`, () => {
            const enabled_field = { field_id: 62, type: "file" };
            const disabled_field = { field_id: 38, type: "file " };
            const tracker_fields = [enabled_field, disabled_field];
            isDisabled.mockImplementation((field) => field === disabled_field);

            const result = getAllFileFields(tracker_fields);

            expect(result).toEqual([enabled_field]);
        });
    });

    describe(`getFirstFileField()`, () => {
        it(`Given a tracker with two enabled file fields,
            then it will return one of them`, () => {
            const tracker_fields = [
                { field_id: 85, type: "file" },
                { field_id: 96, type: "float" },
                { field_id: 45, type: "file" },
            ];
            isDisabled.mockReturnValue(false);

            const result = getFirstFileField(tracker_fields);

            expect(result.type).toEqual("file");
        });

        it(`Given a tracker with only one enabled file field,
            then it will return it`, () => {
            const enabled_field = { field_id: 62, type: "file" };
            const disabled_field = { field_id: 38, type: "file " };
            const tracker_fields = [enabled_field, disabled_field];
            isDisabled.mockImplementation((field) => field === disabled_field);

            const result = getFirstFileField(tracker_fields);

            expect(result).toEqual(enabled_field);
        });

        it(`Given a tracker with no enabled file field,
            then it will return null`, () => {
            const disabled_field = { field_id: 64, type: "file" };
            const tracker_fields = [disabled_field];
            isDisabled.mockImplementation((field) => field === disabled_field);

            const result = getFirstFileField(tracker_fields);

            expect(result).toBeNull();
        });
    });
});
