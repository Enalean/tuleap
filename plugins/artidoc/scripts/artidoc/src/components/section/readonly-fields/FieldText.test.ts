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

import { describe, expect, it } from "vitest";
import { createGettext } from "vue3-gettext";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { ReadonlyFieldText } from "@/sections/readonly-fields/ReadonlyFields";
import FieldText from "@/components/section/readonly-fields/FieldText.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";

const field_text: ReadonlyFieldText = {
    type: "text",
    label: "String Field",
    display_type: "column",
    value: "The first field",
};

describe("FieldText", () => {
    const getWrapper = (): VueWrapper => {
        return shallowMount(FieldText, {
            props: {
                field_text,
            },
            global: {
                plugins: [createGettext({ silent: true }), VueDOMPurifyHTML],
            },
        });
    };

    it("should display the string field label", () => {
        const wrapper = getWrapper();
        const label = wrapper.find("label");
        expect(label.exists()).toBe(true);
        expect(label.text()).toStrictEqual(field_text.label);
    });

    it("should display the string field value", () => {
        const wrapper = getWrapper();
        const paragraph = wrapper.find("p");
        expect(paragraph.exists()).toBe(true);
        expect(paragraph.text()).toStrictEqual(field_text.value);
    });
});
