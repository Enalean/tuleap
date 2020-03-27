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

import { ColumnDefinition, Mapping } from "../../type";
import { ColumnState } from "./type";
import { RootState } from "../type";

export const accepted_trackers_ids = (
    column_state: ColumnState,
    getters: [],
    root_state: RootState
) => (column: ColumnDefinition): number[] => {
    const trackers = root_state.trackers;

    return column.mappings.reduce((trackers_ids: number[], mapping: Mapping) => {
        const tracker = trackers.find((tracker) => tracker.id === mapping.tracker_id);

        if (mapping.accepts.length > 0 && tracker && tracker.can_update_mapped_field) {
            trackers_ids.push(mapping.tracker_id);
        }

        return trackers_ids;
    }, []);
};
