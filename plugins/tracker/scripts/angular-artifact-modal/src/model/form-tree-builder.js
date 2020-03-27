/*
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

import { CONTAINER_FIELDSET } from "../../../constants/fields-constants.js";

const white_listed_fields = [
    CONTAINER_FIELDSET,
    "column",
    "linebreak",
    "separator",
    "staticrichtext",
    "sb",
    "msb",
    "rb",
    "cb",
    "int",
    "string",
    "float",
    "text",
    "art_link",
    "burndown",
    "cross",
    "aid",
    "atid",
    "priority",
    "computed",
    "subby",
    "luby",
    "subon",
    "lud",
    "file",
    "perm",
    "date",
    "tbl",
];

export function buildFormTree(tracker) {
    return tracker.structure
        .map((field) => recursiveGetCompleteField(field, tracker.fields))
        .filter((field) => field !== null);
}

function recursiveGetCompleteField(structure_field, all_fields) {
    const complete_field = all_fields.find((field) => field.field_id === structure_field.id);

    if (complete_field === undefined) {
        return null;
    }

    if (!white_listed_fields.includes(complete_field.type)) {
        return null;
    }

    complete_field.template_url = "field-" + complete_field.type + ".tpl.html";

    if (structure_field.content !== null) {
        var content = structure_field.content
            .map((sub_field) => recursiveGetCompleteField(sub_field, all_fields))
            .filter((field) => field !== null);

        if (complete_field.type === CONTAINER_FIELDSET || complete_field.type === "column") {
            if (content.length === 0) {
                return null;
            }
        }

        complete_field.content = content;
    }

    return complete_field;
}
