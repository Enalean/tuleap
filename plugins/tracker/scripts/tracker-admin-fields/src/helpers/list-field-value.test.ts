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

import { describe, expect, it } from "vitest";
import type {
    ListFieldStructure,
    StaticBoundListField,
    UserBoundListField,
    UserGroupBoundListField,
} from "@tuleap/plugin-tracker-rest-api-types";
import { CHECKBOX_FIELD, LIST_BIND_UGROUPS } from "@tuleap/plugin-tracker-constants";
import { isStaticListField, listFieldValue } from "./list-field-value";
import { RegisteredUserWithAvatarTestBuilder } from "../tests/builders/RegisteredUserWithAvatarTestBuilder";
import { UserGroupRepresentationTestBuilder } from "../tests/builders/UserGroupRepresentationTestBuilder";
import { StaticListItemTestBuilder } from "../tests/builders/StaticListItemTestBuilder";
import { StaticBoundListFieldTestBuilder } from "../tests/builders/StaticBoundListFieldTestBuilder";
import { UserBoundListFieldTestBuilder } from "../tests/builders/UserBoundListFieldTestBuilder";

describe("list-field-value", () => {
    describe("listFieldValue", () => {
        it("returns the filtered value if a list is a static list", () => {
            const field: StaticBoundListField =
                StaticBoundListFieldTestBuilder.aStaticBoundListField()
                    .withValues(
                        StaticListItemTestBuilder.aStaticListItem(1).build(),
                        StaticListItemTestBuilder.aStaticListItem(2).isHidden().build(),
                        StaticListItemTestBuilder.aStaticListItem(3).build(),
                    )
                    .build();

            const expected_result = [
                StaticListItemTestBuilder.aStaticListItem(1).build(),
                StaticListItemTestBuilder.aStaticListItem(3).build(),
            ];
            expect(listFieldValue(field)).toStrictEqual(expected_result);
        });
        it("returns the whole list value when the field values is not a static list", () => {
            const field: UserBoundListField = UserBoundListFieldTestBuilder.aUserBoundListField()
                .withValues(
                    {
                        id: 1,
                        label: "user 1 (user1)",
                        user_reference:
                            RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar()
                                .withId(101)
                                .build(),
                    },
                    {
                        id: 2,
                        label: "user 2 (user2)",
                        user_reference:
                            RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar()
                                .withId(102)
                                .build(),
                    },
                )
                .build();

            expect(listFieldValue(field)).toStrictEqual(field.values);
        });
        describe("isStaticListField", () => {
            const user_bound_field: UserBoundListField =
                UserBoundListFieldTestBuilder.aUserBoundListField()
                    .withValues({
                        id: 1,
                        label: "user 1 (user1)",
                        user_reference:
                            RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar()
                                .withId(101)
                                .build(),
                    })
                    .build();

            const user_group_bound_field: UserGroupBoundListField = {
                field_id: 25,
                label: "Users Group",
                name: "users_group",
                required: false,
                type: CHECKBOX_FIELD,
                default_value: [],
                bindings: {
                    type: LIST_BIND_UGROUPS,
                },
                values: [
                    {
                        id: "101_3",
                        label: "Project member",
                        ugroup_reference:
                            UserGroupRepresentationTestBuilder.aUserGroupListFieldValue().buildWithId(
                                "101_3",
                            ),
                    },
                ],
            };

            const static_field: StaticBoundListField =
                StaticBoundListFieldTestBuilder.aStaticBoundListField()
                    .withDefaultValues(StaticListItemTestBuilder.aStaticListItem(1).build())
                    .build();

            it.each([
                [true, "static list", static_field],
                [false, "user group list", user_group_bound_field],
                [false, "user list", user_bound_field],
            ])(
                `It return %s when the list type is a %s`,
                (result, php_date_format, list_field: ListFieldStructure) => {
                    expect(isStaticListField(list_field)).toBe(result);
                },
            );
        });
    });
});
