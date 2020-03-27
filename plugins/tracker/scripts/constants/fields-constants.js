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

export const CONTAINER_FIELDSET = "fieldset";

export const STRUCTURAL_FIELDS = [
    "column",
    CONTAINER_FIELDSET,
    "linebreak",
    "separator",
    "staticrichtext",
];

export const READ_ONLY_FIELDS = [
    "aid",
    "atid",
    "burndown",
    "cross",
    "luby",
    "lud",
    "priority",
    "subby",
    "subon",
];

export const COMPUTED_FIELD = "computed";
export const SELECTBOX_FIELD = "sb";
export const DATE_FIELD = "date";
export const INT_FIELD = "int";
export const FLOAT_FIELD = "float";

export const LIST_BIND_STATIC = "static";
export const LIST_BIND_UGROUPS = "ugroups";
export const LIST_BIND_USERS = "users";

export const TEXT_FIELD = "text";
export const TEXT_FORMAT_TEXT = "text";
export const TEXT_FORMAT_HTML = "html";

export const FILE_FIELD = "file";

export const FIELD_PERMISSION_READ = "read";
export const FIELD_PERMISSION_CREATE = "create";
export const FIELD_PERMISSION_UPDATE = "update";
