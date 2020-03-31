/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import localVue from "../../../helpers/local-vue.js";

import PermissionsSelector from "./PermissionsSelector.vue";

describe("PermissionsSelector", () => {
    let factory;

    beforeEach(() => {
        factory = (props = {}) => {
            return shallowMount(PermissionsSelector, {
                localVue,
                propsData: { ...props },
            });
        };
    });

    it("Display the list of selected user groups", () => {
        const permission_label = "My permission label";

        const ugroup_1 = { id: "102_3", label: "Project members" };
        const ugroup_2 = { id: "178", label: "My group" };
        const selected_ugroup_1 = { id: "789", label: "My selected group 1" };
        const selected_ugroup_2 = { id: "790", label: "My selected group 2" };

        const wrapper = factory({
            label: permission_label,
            project_ugroups: [ugroup_1, selected_ugroup_1, selected_ugroup_2, ugroup_2],
            selected_ugroups: [selected_ugroup_1, selected_ugroup_2],
        });

        expect(wrapper.text()).toContain(permission_label);
        const all_options = wrapper.get("select").findAll("option");
        expect(all_options.length).toBe(4);
        const selected_option_wrappers = all_options.wrappers.filter(
            (option_wrapper) => option_wrapper.element.selected
        );
        expect(selected_option_wrappers.length).toBe(2);
    });

    it("Select new user groups", () => {
        const ugroup_1 = { id: "177", label: "My group 177" };
        const ugroup_2 = { id: "178", label: "My group 178" };

        const wrapper = factory({
            label: "Permission label",
            project_ugroups: [ugroup_1, ugroup_2],
            selected_ugroups: [],
        });

        wrapper.get("select").setValue(ugroup_1.id);
        const emitted_input = wrapper.emitted("input");
        expect(emitted_input.length).toBe(1);
        expect(emitted_input[0]).toEqual([[{ id: ugroup_1.id }]]);
    });

    it("Refresh selected user groups on fresh information", async () => {
        const ugroup_1 = { id: "177", label: "My group 177" };
        const ugroup_2 = { id: "178", label: "My group 178" };

        const wrapper = factory({
            label: "Permission label",
            project_ugroups: [ugroup_1, ugroup_2],
            selected_ugroups: [],
        });

        wrapper.setProps({
            label: "Permission label",
            project_ugroups: [ugroup_1, ugroup_2],
            selected_ugroups: [ugroup_2],
        });
        await wrapper.vm.$nextTick();

        const all_options = wrapper.get("select").findAll("option");
        const selected_option_wrappers = all_options.wrappers.filter(
            (option_wrapper) => option_wrapper.element.selected
        );

        expect(selected_option_wrappers.length).toBe(1);
    });
});
