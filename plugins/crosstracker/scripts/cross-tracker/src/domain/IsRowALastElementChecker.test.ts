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
import { isLastChildForDirection } from "./IsRowALastElementChecker";
import { FORWARD_DIRECTION, NO_DIRECTION } from "./ArtifactsTable";

describe("isRowALastElementChecker", () => {
    const parent_row_uuid = uuidv4();

    const parent_row_entry: RowEntry = {
        parent_row_uuid: null,
        row: {
            row_uuid: parent_row_uuid,
            artifact_id: 1,
            artifact_uri: "/plugins/tracker/?aid=2",
            cells: new Map(),
            expected_number_of_forward_links: 1,
            expected_number_of_reverse_links: 1,
            direction: NO_DIRECTION,
        },
    };

    const child_row_entry: RowEntry = {
        parent_row_uuid,
        row: {
            row_uuid: uuidv4(),
            artifact_id: 2,
            artifact_uri: "/plugins/tracker/?aid=2",
            cells: new Map(),
            expected_number_of_forward_links: 0,
            expected_number_of_reverse_links: 1,
            direction: FORWARD_DIRECTION,
        },
    };

    const other_child_entry: RowEntry = {
        parent_row_uuid: parent_row_uuid,
        row: {
            row_uuid: uuidv4(),
            artifact_id: 3,
            artifact_uri: "/plugins/tracker/?aid=1",
            cells: new Map(),
            expected_number_of_forward_links: 1,
            expected_number_of_reverse_links: 0,
            direction: FORWARD_DIRECTION,
        },
    };

    it("only grand son is last element", () => {
        const row_entries = [parent_row_entry, child_row_entry, other_child_entry];

        const child_result = isLastChildForDirection(row_entries, child_row_entry);
        expect(child_result).toBe(false);

        const grandson_result = isLastChildForDirection(row_entries, other_child_entry);
        expect(grandson_result).toBe(true);
    });
});
