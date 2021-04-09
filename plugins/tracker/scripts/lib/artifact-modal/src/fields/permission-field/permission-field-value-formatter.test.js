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

import { formatPermissionFieldValue } from "./permission-field-value-formatter.js";

describe("permission-field-value-formatter -", () => {
    describe("formatPermissionFieldValue() -", () => {
        it("Given a field value that was undefined, then it will return null", () => {
            const result = formatPermissionFieldValue(undefined);

            expect(result).toBe(null);
        });

        it("Given a field value object, it will only keep field_id and value attributes", () => {
            const field_value = {
                field_id: 166,
                label: "stallboard",
                permissions: ["read", "update", "create"],
                value: {
                    is_used_by_default: true,
                    granted_groups: ["1", "101"],
                },
            };

            const result = formatPermissionFieldValue(field_value);

            expect(result).toEqual({
                field_id: 166,
                value: {
                    is_used_by_default: true,
                    granted_groups: ["1", "101"],
                },
            });
        });
    });
});
