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

import { describe, expect, it } from "vitest";
import type { RowEntry } from "./TableDataStore";
import { hasExpandableLinks } from "./CheckExpandableLink";
import { v4 as uuidv4 } from "uuid";

describe("CheckExpandableLink", () => {
    it.each([
        [0, 0, 0, false],
        [1, 2, 0, true],
        [0, 1, 1, false],
        [0, 1, 0, true],
        [1, 1, 1, true],
        [1, 0, 0, true],
        [1, 1, 1, true],
    ])(
        "when row as %d forward links and %d reverse links, and row is at level %d, then it expandable property should be %s",
        (number_of_forward_links, number_of_reverse_links, level, is_expandable) => {
            const row_entry: RowEntry = {
                parent_row_uuid: null,
                row: {
                    row_uuid: uuidv4(),
                    artifact_id: 1,
                    artifact_uri: "/plugins/tracker/?aid=2",
                    cells: new Map(),
                    expected_number_of_forward_links: number_of_forward_links,
                    expected_number_of_reverse_links: number_of_reverse_links,
                    direction: "forward",
                },
            };

            const result = hasExpandableLinks(row_entry, level);
            expect(is_expandable).toBe(result);
        },
    );
});
