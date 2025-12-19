/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { MULTI_SELECTBOX_FIELD, SELECTBOX_FIELD } from "@tuleap/plugin-tracker-constants";
import { StaticBoundListFieldTestBuilder } from "../../../tests/builders/StaticBoundListFieldTestBuilder";
import type {
    ListFieldStructure,
    StaticListItem,
    UserBoundListItem,
    UserGroupBoundListItem,
} from "@tuleap/plugin-tracker-rest-api-types";
import { RegisteredUserWithAvatarTestBuilder } from "../../../tests/builders/RegisteredUserWithAvatarTestBuilder";
import { UserBoundListFieldTestBuilder } from "../../../tests/builders/UserBoundListFieldTestBuilder";
import { StaticListItemTestBuilder } from "../../../tests/builders/StaticListItemTestBuilder";
import { UserGroupBoundListFieldTestBuilder } from "../../../tests/builders/UserGroupBoundListFieldTestBuilder";
import { UserGroupRepresentationTestBuilder } from "../../../tests/builders/UserGroupRepresentationTestBuilder";
import SelectBoxOptions from "./SelectBoxOptions.vue";

describe("SelectBoxOptions", () => {
    const getWrapper = (field: ListFieldStructure): VueWrapper =>
        shallowMount(SelectBoxOptions, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
            props: {
                field,
            },
        });

    it("should display the static values as options", () => {
        const values: StaticListItem[] = [
            StaticListItemTestBuilder.aStaticListItem(1001)
                .withLabel("Foo")
                .withColor("fiesta-red")
                .isHidden()
                .build(),
            StaticListItemTestBuilder.aStaticListItem(1002)
                .withLabel("Bar")
                .withColor("acid-green")
                .build(),
            StaticListItemTestBuilder.aStaticListItem(1003)
                .withLabel("Baz")
                .withColor("deep-blue")
                .build(),
            StaticListItemTestBuilder.aStaticListItem(1004)
                .withLabel("Zob")
                .withColor("clockwork-orange")
                .build(),
        ];

        const wrapper = getWrapper(
            StaticBoundListFieldTestBuilder.aStaticBoundListField(MULTI_SELECTBOX_FIELD)
                .withRequiredValue()
                .withValues(...values)
                .withDefaultValues(values[1], values[3])
                .build(),
        );

        const [option_1, option_2, option_3] = wrapper.findAll("option");

        expect(option_1.attributes("value")).toBe(values[1].id.toString());
        expect(option_1.attributes("selected")).toBeDefined();

        expect(option_2.attributes("value")).toBe(values[2].id.toString());
        expect(option_2.attributes("selected")).not.toBeDefined();

        expect(option_3.attributes("value")).toBe(values[3].id.toString());
        expect(option_3.attributes("selected")).toBeDefined();
    });

    it("should display the user values as options", () => {
        const values = [
            {
                id: 102,
                user_reference: RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar()
                    .withId(102)
                    .build(),
            } as UserBoundListItem,
            {
                id: 103,
                user_reference: RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar()
                    .withId(103)
                    .build(),
            } as UserBoundListItem,
        ];

        const wrapper = getWrapper(
            UserBoundListFieldTestBuilder.aUserBoundListField(SELECTBOX_FIELD)
                .withRequiredValue()
                .withValues(...values)
                .withDefaultValues(values[1])
                .build(),
        );

        const [option_1, option_2] = wrapper.findAll("option");

        expect(option_1.attributes("value")).toBe(values[0].id.toString());
        expect(option_1.attributes("selected")).not.toBeDefined();
        expect(option_1.text()).toBe(values[0].user_reference.real_name);

        expect(option_2.attributes("value")).toBe(values[1].id.toString());
        expect(option_2.attributes("selected")).toBeDefined();
        expect(option_2.text()).toBe(values[1].user_reference.real_name);
    });

    it("should display the user groups values as options", () => {
        const values = [
            {
                id: "102",
                label: "",
                ugroup_reference:
                    UserGroupRepresentationTestBuilder.aUserGroupListFieldValue().buildWithId(
                        "102",
                    ),
            } as UserGroupBoundListItem,
            {
                id: "103",
                label: "",
                ugroup_reference:
                    UserGroupRepresentationTestBuilder.aUserGroupListFieldValue().buildWithId(
                        "103",
                    ),
            } as UserGroupBoundListItem,
        ];

        const wrapper = getWrapper(
            UserGroupBoundListFieldTestBuilder.aUserGroupBoundListField(SELECTBOX_FIELD)
                .withRequiredValue()
                .withValues(...values)
                .withDefaultValues(values[1])
                .build(),
        );

        const [option_1, option_2] = wrapper.findAll("option");

        expect(option_1.attributes("value")).toBe(values[0].id.toString());
        expect(option_1.attributes("selected")).not.toBeDefined();
        expect(option_1.text()).toBe(values[0].ugroup_reference.label);

        expect(option_2.attributes("value")).toBe(values[1].id.toString());
        expect(option_2.attributes("selected")).toBeDefined();
        expect(option_2.text()).toBe(values[1].ugroup_reference.label);
    });

    it("should insert the NONE_VALUE when the field is not required", () => {
        const wrapper = getWrapper(
            StaticBoundListFieldTestBuilder.aStaticBoundListField(MULTI_SELECTBOX_FIELD)
                .withValues(
                    StaticListItemTestBuilder.aStaticListItem(1001)
                        .withLabel("Foo")
                        .withColor("fiesta-red")
                        .isHidden()
                        .build(),
                )
                .build(),
        );

        expect(wrapper.find("[data-test=none-value]").exists()).toBe(true);
    });
});
