/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

import type { FileLine, GroupOfLines, GroupType } from "./types";
import { ADDED_GROUP, DELETED_GROUP, UNMOVED_GROUP } from "./types";

export interface GroupSideBySideLines {
    buildLineToGroupMap: () => Map<number, GroupOfLines>;
    buildFirstLineToGroupMap: () => Map<number, GroupOfLines>;
}

export const SideBySideLineGrouper = (lines: readonly FileLine[]): GroupSideBySideLines => {
    const groups = groupLinesByChangeType(lines);
    return {
        buildLineToGroupMap: (): Map<number, GroupOfLines> =>
            groups.reduce((accumulator, group) => {
                group.unidiff_offsets.forEach((offset) => {
                    accumulator.set(offset, group);
                });
                return accumulator;
            }, new Map<number, GroupOfLines>()),
        buildFirstLineToGroupMap: (): Map<number, GroupOfLines> =>
            groups.reduce((accumulator, group) => {
                const first_unidiff_index = group.unidiff_offsets[0];
                accumulator.set(first_unidiff_index, group);
                return accumulator;
            }, new Map<number, GroupOfLines>()),
    };
};

function groupLinesByChangeType(file_lines: readonly FileLine[]): GroupOfLines[] {
    return file_lines.reduce((accumulator: GroupOfLines[], line: FileLine): GroupOfLines[] => {
        const current_change_type = getChangeType(line);
        const previous_group = accumulator[accumulator.length - 1];

        if (previous_group && previous_group.type === current_change_type) {
            previous_group.unidiff_offsets.push(line.unidiff_offset);
            return accumulator;
        }

        const new_group: GroupOfLines = {
            type: current_change_type,
            unidiff_offsets: [line.unidiff_offset],
            has_initial_comment_placeholder: false,
        };

        accumulator.push(new_group);

        return accumulator;
    }, []);
}

function getChangeType(line: FileLine): GroupType {
    if (line.new_offset === null) {
        return DELETED_GROUP;
    }
    if (line.old_offset === null) {
        return ADDED_GROUP;
    }
    return UNMOVED_GROUP;
}
