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

import type { UserBoundListField, UserBoundListItem } from "@tuleap/plugin-tracker-rest-api-types";
import { LIST_BIND_USERS, CHECKBOX_FIELD } from "@tuleap/plugin-tracker-constants";

export class UserBoundListFieldTestBuilder {
    private readonly field_id = 18;
    private readonly label = "Users";
    private readonly name = "users";
    private readonly required = false;
    private readonly type = CHECKBOX_FIELD;
    private readonly default_value = [];
    private readonly bindings = {
        type: LIST_BIND_USERS,
    };

    private values: ReadonlyArray<UserBoundListItem> = [];

    private constructor() {}

    public static aUserBoundListField(): UserBoundListFieldTestBuilder {
        return new UserBoundListFieldTestBuilder();
    }

    public withValues(...values: ReadonlyArray<UserBoundListItem>): UserBoundListFieldTestBuilder {
        this.values = values;
        return this;
    }

    public build(): UserBoundListField {
        return {
            field_id: this.field_id,
            label: this.label,
            name: this.name,
            required: this.required,
            type: this.type,
            default_value: this.default_value,
            bindings: this.bindings,
            values: this.values,
        };
    }
}
