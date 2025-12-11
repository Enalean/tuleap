/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

import type {
    ListFieldItem,
    ListFieldStructure,
    StaticBoundListField,
    StaticListItem,
    UserBoundListItem,
} from "@tuleap/plugin-tracker-rest-api-types";
import { LIST_BIND_STATIC } from "@tuleap/plugin-tracker-constants";

export function listFieldValue(field: ListFieldStructure): ReadonlyArray<ListFieldItem> {
    if (isStaticListField(field)) {
        return field.values.filter((value) => !value.is_hidden);
    }
    return field.values;
}

export function isStaticListField(field: ListFieldStructure): field is StaticBoundListField {
    return field.bindings.type === LIST_BIND_STATIC;
}

export function isStaticListValue(value: ListFieldItem): value is StaticListItem {
    return "value_color" in value;
}

export function isUserBoundListValue(value: ListFieldItem): value is UserBoundListItem {
    return "user_reference" in value;
}
