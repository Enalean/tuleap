/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import type { Folder, Property, ListValue } from "../../../type";
import { getItemStatus, updateStatusProperty } from "./status-property-helper";

describe("status-property-helper", () => {
    describe("updateStatusProperty", () => {
        it("Given status is provided in properties array, then we extract and update the status item's key", () => {
            const list_value = [
                {
                    id: 103,
                } as ListValue,
            ];

            const property: Property = {
                short_name: "status",
                list_value: list_value,
            } as Property;

            const item = {
                id: 7,
                type: "folder",
            } as Folder;

            updateStatusProperty(property, item);

            expect(item.status).toBe("rejected");
        });
    });
    describe("getItemStatus", () => {
        it("Given status is provided in properties array, then we extract and return its value", () => {
            const list_value = [
                {
                    id: 103,
                } as ListValue,
            ];

            const property: Property = {
                short_name: "status",
                list_value: list_value,
            } as Property;

            expect(getItemStatus(property)).toBe("rejected");
        });

        it("Status is none by default", () => {
            const property: Property = {} as Property;

            expect(getItemStatus(property)).toBe("none");
        });
    });
});
