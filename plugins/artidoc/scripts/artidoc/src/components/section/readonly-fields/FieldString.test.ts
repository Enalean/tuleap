/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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
import { createGettext } from "vue3-gettext";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ReadonlyFieldString } from "@/sections/readonly-fields/ReadonlyFieldsCollection";
import FieldString from "@/components/section/readonly-fields/FieldString.vue";

const field_string: ReadonlyFieldString = {
    type: "string",
    label: "String Field",
    display_type: "column",
    value: "The first field",
};

describe("FieldString", () => {
    const getWrapper = (): VueWrapper => {
        return shallowMount(FieldString, {
            props: {
                field_string,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    };

    it("should display the string field label", () => {
        const wrapper = getWrapper();
        const label = wrapper.find("label");
        expect(label.exists()).toBe(true);
        expect(label.text()).toStrictEqual(field_string.label);
    });

    it("should display the string field value", () => {
        const wrapper = getWrapper();
        const paragraph = wrapper.find("p");
        expect(paragraph.exists()).toBe(true);
        expect(paragraph.text()).toStrictEqual(field_string.value);
    });
});
