/*
 * Copyright (c) Enalean, 2017-present. All Rights Reserved.
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

const STRUCTURAL_FIELDS = ["column", "fieldset", "linebreak", "separator", "staticrichtext"];

const READ_ONLY_FIELDS = [
    "aid",
    "atid",
    "burndown",
    "cross",
    "luby",
    "lud",
    "priority",
    "subby",
    "subon"
];

const COMPUTED_FIELD = "computed";
const SELECTBOX_FIELD = "sb";
const DATE_FIELD = "date";
const INT_FIELD = "int";
const FLOAT_FIELD = "float";
const LIST_BIND_STATIC = "static";
const LIST_BIND_UGROUPS = "ugroups";
const LIST_BIND_USERS = "users";
const TEXT_FORMAT_TEXT = "text";
const TEXT_FORMAT_HTML = "html";
const CONTAINER_FIELDSET = "fieldset";

export {
    STRUCTURAL_FIELDS,
    READ_ONLY_FIELDS,
    COMPUTED_FIELD,
    SELECTBOX_FIELD,
    DATE_FIELD,
    INT_FIELD,
    FLOAT_FIELD,
    LIST_BIND_STATIC,
    LIST_BIND_UGROUPS,
    LIST_BIND_USERS,
    TEXT_FORMAT_TEXT,
    TEXT_FORMAT_HTML,
    CONTAINER_FIELDSET
};
