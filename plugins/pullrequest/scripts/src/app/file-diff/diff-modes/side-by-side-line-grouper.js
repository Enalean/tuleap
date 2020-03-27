/*
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

const UNMOVED_GROUP = "unmoved";
const DELETED_GROUP = "deleted";
const ADDED_GROUP = "added";

function buildLineGroups(lines) {
    const groups = groupLinesByChangeType(lines);

    const line_to_group_map = buildLinesToGroupMap(groups);
    const first_line_to_group_map = buildFirstLineToGroupMap(groups);
    return {
        line_to_group_map,
        first_line_to_group_map,
    };
}

function groupLinesByChangeType(lines) {
    return lines.reduce(buildGroups, []);
}

const buildGroups = (accumulator, line, index, array) => {
    const change_type = getChangeType(line);
    if (index > 0) {
        const previous_line = array[index - 1];
        if (lineHasSameChangeTypeAsPreviousLine(previous_line, change_type)) {
            line.group = previous_line.group;
            line.group.unidiff_offsets.push(line.unidiff_offset);
            return accumulator;
        }
    }
    const new_group = {
        type: change_type,
        unidiff_offsets: [line.unidiff_offset],
    };
    line.group = new_group;
    accumulator.push(new_group);
    return accumulator;
};

function lineHasSameChangeTypeAsPreviousLine(previous_line, change_type) {
    return previous_line.group.type === change_type;
}

function buildFirstLineToGroupMap(groups) {
    return groups.reduce((accumulator, group) => {
        const first_unidiff_index = group.unidiff_offsets[0];
        accumulator.set(first_unidiff_index, group);
        return accumulator;
    }, new Map());
}

function buildLinesToGroupMap(groups) {
    return groups.reduce((accumulator, group) => {
        group.unidiff_offsets.forEach((offset) => {
            accumulator.set(offset, group);
        });
        return accumulator;
    }, new Map());
}

function getChangeType(line) {
    if (line.new_offset === null) {
        return DELETED_GROUP;
    }
    if (line.old_offset === null) {
        return ADDED_GROUP;
    }
    return UNMOVED_GROUP;
}

export { buildLineGroups, UNMOVED_GROUP, DELETED_GROUP, ADDED_GROUP };
