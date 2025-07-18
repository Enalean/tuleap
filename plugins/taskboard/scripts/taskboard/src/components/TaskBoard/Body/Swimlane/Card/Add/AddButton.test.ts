/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { shallowMount } from "@vue/test-utils";
import AddButton from "./AddButton.vue";
import { getGlobalTestOptions } from "../../../../../../helpers/global-options-for-test";

describe("AddButton", () => {
    it("displays the button if not in add mode", () => {
        const wrapper = shallowMount(AddButton, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                label: "Lorem",
                is_in_add_mode: false,
            },
        });

        expect(wrapper.find("[data-test=add-in-place-button]").exists()).toBe(true);
    });

    it("does not display the button if in add mode", () => {
        const wrapper = shallowMount(AddButton, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                label: "Lorem",
                is_in_add_mode: true,
            },
        });

        expect(wrapper.find("[data-test=add-in-place-button]").exists()).toBe(false);
    });

    it("propagates the click event", () => {
        const wrapper = shallowMount(AddButton, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                label: "Lorem",
                is_in_add_mode: false,
            },
        });

        wrapper.trigger("click");
        expect(wrapper.emitted().click).toBeTruthy();
    });

    it("displays the given label in the button", () => {
        const wrapper = shallowMount(AddButton, {
            global: { ...getGlobalTestOptions({}) },
            props: {
                label: "Lorem",
                is_in_add_mode: false,
            },
        });

        expect(wrapper.text()).toBe("Lorem");
        expect(wrapper.classes("taskboard-add-in-place-button-with-label")).toBe(true);
    });
});
