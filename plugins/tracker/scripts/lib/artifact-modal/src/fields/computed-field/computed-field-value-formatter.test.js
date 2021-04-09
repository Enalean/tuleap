/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { formatComputedFieldValue } from "./computed-field-value-formatter.js";

describe("computed-field-value-formatter", () => {
    describe("formatComputedFieldValue() -", () => {
        it(`Given a field value that was undefined, then it will return null`, () => {
            const result = formatComputedFieldValue(undefined);

            expect(result).toBe(null);
        });

        it(`Given that the field value was set to autocomputed
            then it will return the field without its manual value`, () => {
            const field_value = {
                field_id: 415,
                is_autocomputed: true,
                label: "heresiologist",
                manual_value: 4,
                permissions: ["read", "update", "create"],
                value: 10,
            };

            const result = formatComputedFieldValue(field_value);

            expect(result).toEqual({
                field_id: 415,
                is_autocomputed: true,
            });
        });

        describe("Given that the field value was not set to autocomputed", () => {
            it(`and the manual value was null, then it will return null`, () => {
                const field_value = {
                    field_id: 827,
                    is_autocomputed: false,
                    label: "Sangraal",
                    manual_value: null,
                    permissions: ["read", "update", "create"],
                    value: 97,
                };

                const result = formatComputedFieldValue(field_value);

                expect(result).toBe(null);
            });

            it(`and the manual value was not null
                then it will return the field without its is_autocomputed property`, () => {
                const field_value = {
                    field_id: 306,
                    is_autocomputed: false,
                    label: "psalmless",
                    manual_value: 33,
                    permissions: ["read", "update", "create"],
                    value: 88,
                };

                const result = formatComputedFieldValue(field_value);

                expect(result).toEqual({
                    field_id: 306,
                    manual_value: 33,
                });
            });
        });
    });
});
