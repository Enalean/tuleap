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
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import FieldUserGroupsList from "@/components/section/readonly-fields/FieldUserGroupsList.vue";
import type { ReadonlyFieldUserGroupsListValue } from "@/sections/readonly-fields/ReadonlyFields";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import { DISPLAY_TYPE_COLUMN } from "@/sections/readonly-fields/AvailableReadonlyFields";

describe("FieldUserGroupsList", () => {
    const getWrapper = (selected_ugroups: ReadonlyFieldUserGroupsListValue[]): VueWrapper =>
        shallowMount(FieldUserGroupsList, {
            props: {
                user_groups_list_field: ReadonlyFieldStub.userGroupsList(
                    selected_ugroups,
                    DISPLAY_TYPE_COLUMN,
                ),
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

    it("When the field has no values, then it should display an empty state", () => {
        const wrapper = getWrapper([]);

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
        expect(wrapper.findAll("[data-test=user-group-list-item]")).toHaveLength(0);
    });

    it("should display the user groups labels", () => {
        const values = [
            {
                id: "101",
                label: "Project Administrators",
            },
            {
                id: "102",
                label: "Project Members",
            },
        ];
        const wrapper = getWrapper(values);

        const items = wrapper.findAll("[data-test=user-group-list-item]");

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(false);
        expect(items).toHaveLength(2);

        expect(items[0].text()).toBe(values[0].label);
        expect(items[1].text()).toBe(values[1].label);
    });
});
