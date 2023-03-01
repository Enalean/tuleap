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

import { getAllFileFields } from "./file-field-detector";
import * as disabled_field_detector from "../disabled-field-detector";
import type { Field } from "../../../../domain/fields/Field";

describe("file-field-detector", () => {
    let isDisabled: jest.SpyInstance;
    beforeEach(() => {
        isDisabled = jest.spyOn(disabled_field_detector, "isDisabled");
    });

    describe("getAllFileFields()", () => {
        it(`Given a tracker with two enabled file fields,
            then it will return the two file fields`, () => {
            const tracker_fields = [
                { field_id: 62, type: "file" },
                { field_id: 43, type: "string" },
                { field_id: 38, type: "file" },
            ] as Field[];
            isDisabled.mockReturnValue(false);

            const result = getAllFileFields(tracker_fields);

            expect(result).toStrictEqual([
                { field_id: 62, type: "file" },
                { field_id: 38, type: "file" },
            ]);
        });

        it(`Given a tracker with one enabled file field
            and one disabled file field,
            then it will return only the enabled field`, () => {
            const enabled_field = { field_id: 62, type: "file" };
            const disabled_field = { field_id: 38, type: "file " };
            const tracker_fields = [enabled_field, disabled_field] as Field[];
            isDisabled.mockImplementation((field) => field === disabled_field);

            const result = getAllFileFields(tracker_fields);

            expect(result).toStrictEqual([enabled_field]);
        });
    });
});
