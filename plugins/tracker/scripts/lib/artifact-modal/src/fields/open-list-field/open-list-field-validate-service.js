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

import { pick } from "lodash-es";
import {
    LIST_BIND_STATIC,
    LIST_BIND_UGROUPS,
    LIST_BIND_USERS,
} from "@tuleap/plugin-tracker-constants";

export function validateOpenListFieldValue(value_model) {
    if (typeof value_model === "undefined") {
        return null;
    }

    value_model.value.bind_value_objects = value_model.value.bind_value_objects.map(
        (bind_value_object) => {
            if (value_model.bindings.type === LIST_BIND_STATIC) {
                return removeStaticValueUnusedAttributes(bind_value_object);
            } else if (value_model.bindings.type === LIST_BIND_UGROUPS) {
                return removeUgroupsValueUnusedAttributes(bind_value_object);
            } else if (value_model.bindings.type === LIST_BIND_USERS) {
                return removeUsersValueUnusedAttributes(bind_value_object);
            }
        },
    );

    return removeValueModelUnusedAttributes(value_model);
}

function removeStaticValueUnusedAttributes(static_bind_value) {
    return pick(static_bind_value, ["id", "label"]);
}

function removeUgroupsValueUnusedAttributes(ugroups_bind_value) {
    return pick(ugroups_bind_value, ["id", "short_name"]);
}

function removeUsersValueUnusedAttributes(users_bind_value) {
    if (users_bind_value.is_anonymous) {
        return pick(users_bind_value, ["email"]);
    }
    return pick(users_bind_value, ["id", "username", "email"]);
}

function removeValueModelUnusedAttributes(value_model) {
    return pick(value_model, ["field_id", "value"]);
}
