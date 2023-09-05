/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { buildEditableDateFieldValue } from "./date-field-value-builder";
import type { EditableDateFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import moment from "moment";

// Moment does not actually "export default" which leads to `"moment_1.default" is not a function` error (but only in jest)
jest.mock("moment", () => ({
    default: jest.requireActual("moment"),
}));

const getDateField = (is_time_displayed: boolean): EditableDateFieldStructure => {
    return {
        field_id: 824,
        label: "nondrying",
        name: "indisciplined",
        permissions: ["read", "update", "create"],
        type: "date",
        is_time_displayed,
    } as unknown as EditableDateFieldStructure;
};

describe("date-field-value-builder", () => {
    let format: jest.SpyInstance;

    beforeEach(() => {
        format = jest
            .spyOn(moment.fn, "format")
            .mockImplementation(() => "a date formatted using the provided format");
    });

    it.each([
        [false, "YYYY-MM-DD"],
        [true, "YYYY-MM-DD HH:mm"],
    ])(
        "When is_time_displayed is %s, then the time should be formatted using the format %s in the formatted value",
        (is_time_displayed, expected_format) => {
            const date_field = getDateField(is_time_displayed);

            expect(
                buildEditableDateFieldValue(date_field, "2015-05-29T18:09:43+03:00"),
            ).toStrictEqual({
                field_id: date_field.field_id,
                permissions: date_field.permissions,
                type: date_field.type,
                value: "a date formatted using the provided format",
            });

            expect(format).toHaveBeenCalledWith(expected_format);
        },
    );

    it("When the date is null, Then it should return the value as an empty string", () => {
        const result = buildEditableDateFieldValue(getDateField(true), null);

        expect(format).not.toHaveBeenCalled();
        expect(result.value).toBe("");
    });
});
