/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { ColumnDefinition, ListValue, Mapping } from "../../type";
import * as getters from "./column-getters";
import { ColumnState } from "./type";
import { RootState } from "../type";

describe("column-getters", () => {
    let column_state: ColumnState, root_state: RootState;

    beforeEach(() => {
        column_state = {} as ColumnState;
        root_state = {
            trackers: [
                { id: 11, can_update_mapped_field: true },
                { id: 12, can_update_mapped_field: true },
                { id: 13, can_update_mapped_field: false },
            ],
        } as RootState;
    });

    describe("accepted_trackers_ids", () => {
        it("will return a list containing the ids of the trackers accepted by the column", () => {
            const column = {
                mappings: [
                    {
                        tracker_id: 11,
                        accepts: [{ id: 1 }, { id: 2 }],
                    },
                    {
                        tracker_id: 12,
                        accepts: [{ id: 3 }, { id: 4 }],
                    },
                ],
            } as ColumnDefinition;

            expect(getters.accepted_trackers_ids(column_state, [], root_state)(column)).toEqual([
                11,
                12,
            ]);
        });

        it("will return an empty array if the column does not accept any list values", () => {
            const column = {
                mappings: [
                    {
                        tracker_id: 11,
                        accepts: [] as ListValue[],
                    },
                    {
                        tracker_id: 12,
                        accepts: [] as ListValue[],
                    },
                ],
            } as ColumnDefinition;

            expect(getters.accepted_trackers_ids(column_state, [], root_state)(column)).toEqual([]);
        });

        it("will return an empty array when the user cannot update the mapped field", () => {
            const column = {
                mappings: [
                    {
                        tracker_id: 13,
                        accepts: [{ id: 1 }] as ListValue[],
                    },
                ],
            } as ColumnDefinition;

            expect(getters.accepted_trackers_ids(column_state, [], root_state)(column)).toEqual([]);
        });

        it("will return an empty array if the column has no mapping", () => {
            const column = {
                mappings: [] as Mapping[],
            } as ColumnDefinition;

            expect(getters.accepted_trackers_ids(column_state, [], root_state)(column)).toEqual([]);
        });
    });
});
