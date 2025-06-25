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
import type { ReadonlyFieldStaticListValue } from "@/sections/readonly-fields/ReadonlyFields";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import { DISPLAY_TYPE_COLUMN } from "@/sections/readonly-fields/AvailableReadonlyFields";
import FieldStaticList from "@/components/section/readonly-fields/FieldStaticList.vue";
import TlpColorBubble from "@/components/section/readonly-fields/TlpColorBubble.vue";

describe("FieldStaticList", () => {
    const getWrapper = (selected_values: ReadonlyFieldStaticListValue[]): VueWrapper =>
        shallowMount(FieldStaticList, {
            props: {
                static_list_field: ReadonlyFieldStub.staticList(
                    selected_values,
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
        expect(wrapper.findAll("[data-test=static-list-item]")).toHaveLength(0);
    });

    it("should display the static values with a color bubble if they have a color bound to them", () => {
        const wrapper = getWrapper([
            { label: "Red", tlp_color: "red-wine" },
            { label: "Blue", tlp_color: "acid-green" },
            { label: "Green", tlp_color: "deep-blue" },
            { label: "Transparent", tlp_color: "" },
        ]);

        expect(wrapper.findAll("[data-test=static-list-item]")).toHaveLength(4);

        const [red, blue, green, transparent] = wrapper.findAll("[data-test=static-list-item]");

        expect(red.text()).toBe("Red");
        expect(red.findComponent(TlpColorBubble).exists()).toBe(true);

        expect(blue.text()).toBe("Blue");
        expect(blue.findComponent(TlpColorBubble).exists()).toBe(true);

        expect(green.text()).toBe("Green");
        expect(green.findComponent(TlpColorBubble).exists()).toBe(true);

        expect(transparent.text()).toBe("Transparent");
        expect(transparent.findComponent(TlpColorBubble).exists()).toBe(false);
    });
});
