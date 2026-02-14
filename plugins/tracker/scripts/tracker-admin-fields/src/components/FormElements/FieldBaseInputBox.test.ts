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

import { beforeEach, describe, expect, it } from "vitest";
import { shallowMount, type VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import { CHECKBOX_FIELD, RADIO_BUTTON_FIELD } from "@tuleap/plugin-tracker-constants";
import FieldBaseInputBox from "./FieldBaseInputBox.vue";
import type { ListFieldStructure } from "@tuleap/plugin-tracker-rest-api-types";
import { StaticListItemTestBuilder } from "../../tests/builders/StaticListItemTestBuilder";
import { StaticBoundListFieldTestBuilder } from "../../tests/builders/StaticBoundListFieldTestBuilder";
import ColorBadge from "./ColorBadge.vue";
import UserBadge from "./UserBadge.vue";
import { UserBoundListFieldTestBuilder } from "../../tests/builders/UserBoundListFieldTestBuilder";
import { RegisteredUserWithAvatarTestBuilder } from "../../tests/builders/RegisteredUserWithAvatarTestBuilder";

describe("FieldBaseInputBox", () => {
    let field: ListFieldStructure;
    let input_box_type: typeof CHECKBOX_FIELD | typeof RADIO_BUTTON_FIELD = CHECKBOX_FIELD;

    function getWrapper(): VueWrapper {
        return shallowMount(FieldBaseInputBox, {
            props: {
                field,
                input_box_type,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    describe("checkbox input", () => {
        beforeEach(() => {
            input_box_type = CHECKBOX_FIELD;
        });
        it("should display the checkbox element with its default value and badge", () => {
            const default_value = StaticListItemTestBuilder.aStaticListItem(2)
                .withLabel("MX-5")
                .withColor("red-wine")
                .build();

            field = StaticBoundListFieldTestBuilder.aStaticBoundListField(CHECKBOX_FIELD)
                .withDefaultValues(default_value)
                .withValues(
                    StaticListItemTestBuilder.aStaticListItem(1).withLabel("MX-3").build(),
                    default_value,
                )
                .build();

            const wrapper = getWrapper();

            const input_element_list = wrapper.findAll<HTMLInputElement>(
                "[data-test=input-box-field-input]",
            );
            expect(input_element_list.length).toBe(2);

            const label_element_list = wrapper.findAll("[data-test=input-box-label]");
            expect(label_element_list.length).toBe(2);

            expect(label_element_list[0].text()).includes("MX-3");
            expect(label_element_list[0].classes()).includes("tlp-checkbox");
            expect(label_element_list[0].classes().includes("input-box-with-color-container")).toBe(
                false,
            );
            expect(input_element_list[0].attributes("type")).toBe("checkbox");
            expect(input_element_list[0].element.checked).toBe(false);

            expect(label_element_list[1].text()).includes("MX-5");
            expect(label_element_list[1].classes()).includes("tlp-checkbox");
            expect(label_element_list[1].classes().includes("input-box-with-badge-container")).toBe(
                true,
            );
            expect(input_element_list[1].attributes("type")).toBe("checkbox");
            expect(input_element_list[1].element.checked).toBe(true);
        });
    });
    describe("radio input", () => {
        beforeEach(() => {
            input_box_type = RADIO_BUTTON_FIELD;
        });
        it("should display the radio element with its default value and badge", () => {
            const default_value = StaticListItemTestBuilder.aStaticListItem(2)
                .withLabel("MX-5")
                .withColor("red-wine")
                .build();

            field = StaticBoundListFieldTestBuilder.aStaticBoundListField(RADIO_BUTTON_FIELD)
                .withDefaultValues(default_value)
                .withValues(
                    StaticListItemTestBuilder.aStaticListItem(1).withLabel("MX-3").build(),
                    default_value,
                )
                .build();

            const wrapper = getWrapper();

            const input_element_list = wrapper.findAll<HTMLInputElement>(
                "[data-test=input-box-field-input]",
            );
            expect(input_element_list.length).toBe(2);

            const label_element_list = wrapper.findAll("[data-test=input-box-label]");
            expect(label_element_list.length).toBe(2);

            expect(label_element_list[0].text()).includes("MX-3");
            expect(label_element_list[0].classes()).includes("tlp-radio");
            expect(label_element_list[0].classes().includes("input-box-with-badge-container")).toBe(
                false,
            );
            expect(input_element_list[0].attributes("type")).toBe("radio");
            expect(input_element_list[0].element.checked).toBe(false);

            expect(label_element_list[1].text()).includes("MX-5");
            expect(label_element_list[1].classes()).includes("tlp-radio");
            expect(label_element_list[1].classes().includes("input-box-with-badge-container")).toBe(
                true,
            );
            expect(input_element_list[1].attributes("type")).toBe("radio");
            expect(input_element_list[1].element.checked).toBe(true);
        });
    });
    describe("Badge displays", () => {
        it("displays the color badge component if the current list is a static bound list", () => {
            field = StaticBoundListFieldTestBuilder.aStaticBoundListField(CHECKBOX_FIELD)
                .withValues(
                    StaticListItemTestBuilder.aStaticListItem(1).withColor("red-wine").build(),
                )
                .build();
            const wrapper = getWrapper();
            expect(wrapper.findComponent(ColorBadge).exists()).toBe(true);
            expect(wrapper.findComponent(UserBadge).exists()).toBe(false);
        });
        it("displays the user badge component if the current list is a user bound list", () => {
            field = UserBoundListFieldTestBuilder.aUserBoundListField(CHECKBOX_FIELD)
                .withValues({
                    id: 101,
                    label: "User 1",
                    user_reference:
                        RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar().build(),
                })
                .build();
            const wrapper = getWrapper();

            expect(wrapper.findComponent(ColorBadge).exists()).toBe(false);
            expect(wrapper.findComponent(UserBadge).exists()).toBe(true);
        });
    });
    describe("Registered user group handling", () => {
        it("displays the feedback error if the input is user bound to registered users group", () => {
            field = UserBoundListFieldTestBuilder.aUserBoundListField(CHECKBOX_FIELD)
                .withValues({
                    id: 2,
                    label: "Registered users",
                    user_reference:
                        RegisteredUserWithAvatarTestBuilder.aRegisteredUserWithAvatar().build(),
                })
                .withABindingGroupList({ id: "2" })
                .build();

            const wrapper = getWrapper();

            expect(
                wrapper.find<HTMLInputElement>("[data-test=registered-users-group-error]").exists(),
            ).toBe(true);
        });
    });
});
