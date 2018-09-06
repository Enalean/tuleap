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
const LINE_HEIGHT_IN_PX = 20;

function buildLineGroups(lines) {
    const groups = groupLinesByChangeType(lines);

    return buildFirstLineToGroupMap(groups);
}

function groupLinesByChangeType(lines) {
    // Start from the end of the lines to compute the first line of
    // each group more easily.
    // The first line is where the placeholder widget will be set.
    return lines.reduceRight(buildGroups, []);
}

const buildGroups = (accumulator, line, index, array) => {
    const change_type = getChangeType(line);
    if (index < array.length - 1) {
        const previous_line = array[index + 1];
        if (lineHasSameChangeTypeAsPreviousLine(previous_line, change_type)) {
            line.group = previous_line.group;
            updateGroupFirstLineAndHeight(line.group, line.unidiff_offset, index);
            return accumulator;
        }
    }
    const new_group = {
        type: change_type,
        first_line_unidiff_offset: line.unidiff_offset,
        height: computeLineHeightForIndex(index)
    };
    line.group = new_group;
    accumulator.push(new_group);
    return accumulator;
};

function updateGroupFirstLineAndHeight(group, line_unidiff_offset, index) {
    group.first_line_unidiff_offset = line_unidiff_offset;
    group.height += computeLineHeightForIndex(index);
}

function computeLineHeightForIndex(index) {
    if (index === 0) {
        return 0;
    }
    return LINE_HEIGHT_IN_PX;
}

function lineHasSameChangeTypeAsPreviousLine(previous_line, change_type) {
    return previous_line.group.type === change_type;
}

function buildFirstLineToGroupMap(groups) {
    return groups.reduce((accumulator, group) => {
        accumulator.set(group.first_line_unidiff_offset, group);
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
