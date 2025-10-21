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

import type { RowEntry } from "./TableDataStore";
import {
    type ArtifactRow,
    FORWARD_DIRECTION,
    NO_DIRECTION,
    REVERSE_DIRECTION,
} from "./ArtifactsTable";
import { expect, it, describe } from "vitest";
import { checkHasChildrenForReverseDirection } from "./CheckRowHasChildren";
import { v4 as uuidv4 } from "uuid";

describe("CheckRowHasChildren", () => {
    const parent_row: RowEntry = {
        parent_row_uuid: null,
        row: { artifact_id: 1, row_uuid: uuidv4(), direction: NO_DIRECTION } as ArtifactRow,
    };

    const forward_child: RowEntry = {
        parent_row_uuid: parent_row.row.row_uuid,
        row: { artifact_id: 20, row_uuid: uuidv4(), direction: FORWARD_DIRECTION } as ArtifactRow,
    };

    const reverse_child: RowEntry = {
        parent_row_uuid: parent_row.row.row_uuid,
        row: { artifact_id: 30, row_uuid: uuidv4(), direction: REVERSE_DIRECTION } as ArtifactRow,
    };

    it("Given a collection of forward links, it does not have any reverse", () => {
        const row_collection = [parent_row, forward_child];
        const result = checkHasChildrenForReverseDirection(row_collection, forward_child);
        expect(result).toBe(false);
    });

    it("Given a collection of reverse links, it does have reverse links", () => {
        const row_collection = [parent_row, reverse_child];
        const result = checkHasChildrenForReverseDirection(row_collection, reverse_child);
        expect(result).toBe(true);
    });
    it("Given a collection of forward and reverse links, it does have reverse links", () => {
        const row_collection = [parent_row, forward_child, reverse_child];
        const result = checkHasChildrenForReverseDirection(row_collection, forward_child);
        expect(result).toBe(true);
    });
});
