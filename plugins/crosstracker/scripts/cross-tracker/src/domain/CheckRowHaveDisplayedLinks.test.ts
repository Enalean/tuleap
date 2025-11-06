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
import { v4 as uuidv4 } from "uuid";
import { FORWARD_DIRECTION, NO_DIRECTION } from "./ArtifactsTable";
import { isLastVisibleChildWithMoreUnloadedSiblings } from "./CheckRowHaveDisplayedLinks";

describe("CheckRowHaveDisplayedLinks", () => {
    const parent_UUID = uuidv4();

    const child_row1: RowEntry = {
        parent_row_uuid: parent_UUID,
        row: {
            row_uuid: uuidv4(),
            artifact_id: 201,
            artifact_uri: "/plugins/tracker/?aid=201",
            cells: new Map(),
            expected_number_of_forward_links: 0,
            expected_number_of_reverse_links: 1,
            direction: FORWARD_DIRECTION,
        },
    };

    const child_row2: RowEntry = {
        parent_row_uuid: parent_UUID,
        row: {
            row_uuid: uuidv4(),
            artifact_id: 202,
            artifact_uri: "/plugins/tracker/?aid=202",
            cells: new Map(),
            expected_number_of_forward_links: 3,
            expected_number_of_reverse_links: 0,
            direction: FORWARD_DIRECTION,
        },
    };

    const child_row3: RowEntry = {
        parent_row_uuid: parent_UUID,
        row: {
            row_uuid: uuidv4(),
            artifact_id: 203,
            artifact_uri: "/plugins/tracker/?aid=203",
            cells: new Map(),
            expected_number_of_forward_links: 5,
            expected_number_of_reverse_links: 1,
            direction: FORWARD_DIRECTION,
        },
    };

    it(`Given a row with several children,
        When it has less children in collection than the expected number of links,
        Then everything is loaded`, () => {
        const parent_row: RowEntry = {
            parent_row_uuid: null,
            row: {
                row_uuid: parent_UUID,
                artifact_id: 100,
                artifact_uri: "/plugins/tracker/?aid=100",
                cells: new Map(),
                expected_number_of_forward_links: 3,
                expected_number_of_reverse_links: 0,
                direction: NO_DIRECTION,
            },
        };
        const table_data_store_row_list = [parent_row, child_row1, child_row2, child_row3];

        const parent_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            parent_row,
            table_data_store_row_list,
            [],
        );
        expect(parent_is_not_last_children).toBe(false);

        const first_child_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            child_row1,
            table_data_store_row_list,
            [],
        );
        expect(first_child_is_not_last_children).toBe(false);

        const middle_child_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            child_row2,
            table_data_store_row_list,
            [],
        );
        expect(middle_child_is_not_last_children).toBe(false);

        const last_child_is_the_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            child_row3,
            table_data_store_row_list,
            [],
        );
        expect(last_child_is_the_last_children).toBe(false);
    });

    it(`Given a row with several children,
        When it has more children in collection than the expected number of links,
        Then everything is NOT loaded`, () => {
        const parent_row: RowEntry = {
            parent_row_uuid: null,
            row: {
                row_uuid: parent_UUID,
                artifact_id: 100,
                artifact_uri: "/plugins/tracker/?aid=100",
                cells: new Map(),
                expected_number_of_forward_links: 30,
                expected_number_of_reverse_links: 0,
                direction: NO_DIRECTION,
            },
        };
        const table_data_store_row_list = [parent_row, child_row1, child_row2, child_row3];

        const parent_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            parent_row,
            table_data_store_row_list,
            [],
        );
        expect(parent_is_not_last_children).toBe(false);

        const first_child_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            child_row1,
            table_data_store_row_list,
            [],
        );
        expect(first_child_is_not_last_children).toBe(false);

        const middle_child_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            child_row2,
            table_data_store_row_list,
            [],
        );
        expect(middle_child_is_not_last_children).toBe(false);

        const last_child_is_the_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            child_row3,
            table_data_store_row_list,
            [],
        );
        expect(last_child_is_the_last_children).toBe(true);
    });

    it(`Given the row has not finished to load all its artifact links, then everything is loaded`, () => {
        const parent_row: RowEntry = {
            parent_row_uuid: null,
            row: {
                row_uuid: parent_UUID,
                artifact_id: 100,
                artifact_uri: "/plugins/tracker/?aid=100",
                cells: new Map(),
                expected_number_of_forward_links: 30,
                expected_number_of_reverse_links: 0,
                direction: NO_DIRECTION,
            },
        };
        const table_data_store_row_list = [parent_row, child_row1];

        const parent_is_not_last_children = isLastVisibleChildWithMoreUnloadedSiblings(
            parent_row,
            table_data_store_row_list,
            [{ row_uuid: child_row1.row.row_uuid, direction: child_row1.row.direction }],
        );
        expect(parent_is_not_last_children).toBe(false);
    });
});
