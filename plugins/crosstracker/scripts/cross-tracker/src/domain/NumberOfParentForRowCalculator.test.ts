/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import { v4 as uuidv4 } from "uuid";
import type { RowEntry } from "./TableDataStore";
import { getNumberOfParent } from "./NumberOfParentForRowCalculator";

describe("NumberOfParentForRowCalculator", () => {
    const parent_row_uuid = uuidv4();
    const child_row_uuid = uuidv4();
    const grandson_uuid = uuidv4();

    const parent_row_entry: RowEntry = {
        parent_row_uuid: null,
        row: {
            row_uuid: parent_row_uuid,
            artifact_id: 1,
            artifact_uri: "/plugins/tracker/?aid=2",
            cells: new Map(),
            expected_number_of_forward_links: 1,
            expected_number_of_reverse_links: 1,
            direction: "forward",
        },
    };

    const child_row_entry: RowEntry = {
        parent_row_uuid,
        row: {
            row_uuid: child_row_uuid,
            artifact_id: 2,
            artifact_uri: "/plugins/tracker/?aid=2",
            cells: new Map(),
            expected_number_of_forward_links: 0,
            expected_number_of_reverse_links: 1,
            direction: "forward",
        },
    };

    const grandson_row_entry: RowEntry = {
        parent_row_uuid: child_row_uuid,
        row: {
            row_uuid: grandson_uuid,
            artifact_id: 3,
            artifact_uri: "/plugins/tracker/?aid=1",
            cells: new Map(),
            expected_number_of_forward_links: 1,
            expected_number_of_reverse_links: 0,
            direction: "no-direction",
        },
    };

    it("should return 0 for a row with no parent", () => {
        const row_collection = [parent_row_entry];
        const result = getNumberOfParent(row_collection, parent_row_entry);

        expect(result).toBe(0);
    });

    it("should return 1 for a row with one parent", () => {
        const row_collection = [parent_row_entry, child_row_entry];
        const result = getNumberOfParent(row_collection, child_row_entry);

        expect(result).toBe(1);
    });

    it("should return 2 for a row with a parent and a grandparent", () => {
        const row_collection = [grandson_row_entry, parent_row_entry, child_row_entry];
        const result = getNumberOfParent(row_collection, grandson_row_entry);

        expect(result).toBe(2);
    });
});
