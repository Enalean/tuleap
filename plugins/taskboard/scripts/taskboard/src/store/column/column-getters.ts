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

export const accepted_trackers_ids = () => (column: ColumnDefinition): number[] => {
    return column.mappings.reduce((trackers_ids: number[], mapping: Mapping) => {
        if (mapping.accepts.length > 0) {
            trackers_ids.push(mapping.tracker_id);
        }

        return trackers_ids;
    }, []);
};
