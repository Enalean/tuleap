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
import { createTaskboardLocalVue } from "../../../../../../helpers/local-vue-for-test";

describe("AddButon", () => {
    it("propagates the click event", async () => {
        const wrapper = shallowMount(AddButton, {
            localVue: await createTaskboardLocalVue(),
            propsData: {
                label: "Lorem",
            },
        });

        wrapper.trigger("click");
        expect(wrapper.emitted().click).toBeTruthy();
    });

    it("can have no label, therefore display a tlp-tooltip instead", async () => {
        const wrapper = shallowMount(AddButton, {
            localVue: await createTaskboardLocalVue(),
            propsData: {
                label: "",
            },
        });

        expect(wrapper.text()).toBe("");
        expect(wrapper.classes("tlp-tooltip")).toBe(true);
        expect(wrapper.classes("tlp-tooltip-top")).toBe(true);
        expect(wrapper.classes("taskboard-add-in-place-button-with-label")).toBe(false);
    });

    it("displays the given label in the button", async () => {
        const wrapper = shallowMount(AddButton, {
            localVue: await createTaskboardLocalVue(),
            propsData: {
                label: "Lorem",
            },
        });

        expect(wrapper.text()).toBe("Lorem");
        expect(wrapper.classes("tlp-tooltip")).toBe(false);
        expect(wrapper.classes("tlp-tooltip-top")).toBe(false);
        expect(wrapper.classes("taskboard-add-in-place-button-with-label")).toBe(true);
    });
});
