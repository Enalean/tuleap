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
import { createTaskboardLocalVue } from "../../../helpers/local-vue-for-test";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";
import CollapseButton from "./CollapseButton.vue";
import { ColumnDefinition } from "../../../type";

describe("CollapseButton", () => {
    it("Given the column is collapsed, then the button is not displayed", async () => {
        const column: ColumnDefinition = { is_collapsed: true, label: "Done" } as ColumnDefinition;
        const wrapper = shallowMount(CollapseButton, {
            localVue: await createTaskboardLocalVue(),
            propsData: { column }
        });

        expect(wrapper.isEmpty()).toBe(true);
    });

    it("Given the column is expanded, it displays its label as a TLP tooltip", async () => {
        const column: ColumnDefinition = { is_collapsed: false, label: "Done" } as ColumnDefinition;
        const wrapper = shallowMount(CollapseButton, {
            localVue: await createTaskboardLocalVue(),
            propsData: { column }
        });
        expect(wrapper.attributes("data-tlp-tooltip")).toBe('Collapse "Done" column');
        expect(wrapper.classes("tlp-tooltip")).toBe(true);
        expect(wrapper.classes("tlp-tooltip-bottom")).toBe(true);
    });

    it("Given the column is expanded, it displays a focusable button", async () => {
        const column: ColumnDefinition = { is_collapsed: false, label: "Done" } as ColumnDefinition;
        const wrapper = shallowMount(CollapseButton, {
            localVue: await createTaskboardLocalVue(),
            propsData: { column }
        });

        const button = wrapper.find("[data-test=button]");
        expect(button.classes("fa-minus-square")).toBe(true);
        expect(button.attributes("role")).toBe("button");
        expect(button.attributes("tabindex")).toBe("0");
        expect(button.attributes("aria-label")).toBe('Collapse "Done" column');
    });

    it("Given the column is expanded, when user clicks on the button, the column is collapsed", async () => {
        const column: ColumnDefinition = { is_collapsed: false, label: "Done" } as ColumnDefinition;
        const wrapper = shallowMount(CollapseButton, {
            localVue: await createTaskboardLocalVue(),
            mocks: {
                $store: createStoreMock({})
            },
            propsData: { column }
        });

        const button = wrapper.find("[data-test=button]");
        button.trigger("click");
        expect(wrapper.vm.$store.dispatch).toHaveBeenCalledWith("collapseColumn", column);
    });
});
