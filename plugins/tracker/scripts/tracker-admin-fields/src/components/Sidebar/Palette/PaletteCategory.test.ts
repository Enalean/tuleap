/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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
import PaletteCategory from "./PaletteCategory.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-tests";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

describe("PaletteCategory", () => {
    function getWrapper(search: string): VueWrapper {
        return shallowMount(PaletteCategory, {
            props: {
                category: {
                    label: "Category label",
                    fields: [
                        {
                            label: "Field1 label",
                            icon: "icon",
                        },
                        {
                            label: "Field2 label",
                            icon: "icon",
                        },
                    ],
                },
                search,
            },
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it("displays all the fields if the search is empty", () => {
        const wrapper = getWrapper("");

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).toContain("Field1 label");
        expect(wrapper.text()).toContain("Field2 label");
    });

    it("displays all the fields that match the search", () => {
        const wrapper = getWrapper("field1");

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).toContain("Field1 label");
        expect(wrapper.text()).not.toContain("Field2 label");
    });

    it("displays nothing if category is collapsed", async () => {
        const wrapper = getWrapper("");

        await wrapper.find("[data-test=expand-collapse]").trigger("click");

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).not.toContain("Field1 label");
        expect(wrapper.text()).not.toContain("Field2 label");

        await wrapper.find("[data-test=expand-collapse]").trigger("click");

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).toContain("Field1 label");
        expect(wrapper.text()).toContain("Field2 label");
    });

    it("if category is collapsed and user starts to search something, then category is open to display results", async () => {
        const wrapper = getWrapper("");

        await wrapper.find("[data-test=expand-collapse]").trigger("click");

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).not.toContain("Field1 label");
        expect(wrapper.text()).not.toContain("Field2 label");

        await wrapper.setProps({ search: "field1" });

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).toContain("Field1 label");
        expect(wrapper.text()).not.toContain("Field2 label");

        await wrapper.setProps({ search: "" });

        expect(wrapper.text()).toContain("Category label");
        expect(wrapper.text()).toContain("Field1 label");
        expect(wrapper.text()).toContain("Field2 label");
    });
});
