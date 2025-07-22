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

import { describe, expect, it } from "vitest";
import { createGettext } from "vue3-gettext";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldNumeric from "@/components/section/readonly-fields/FieldNumeric.vue";
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import type { ReadonlyFieldNumeric } from "@/sections/readonly-fields/ReadonlyFields";
import { DISPLAY_TYPE_BLOCK } from "@/sections/readonly-fields/AvailableReadonlyFields";

describe("FieldNumeric", () => {
    const getWrapper = (field: ReadonlyFieldNumeric): VueWrapper => {
        return shallowMount(FieldNumeric, {
            props: {
                field,
            },
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });
    };

    it("When the field has no values, then it should display an empty state", () => {
        const wrapper = getWrapper(ReadonlyFieldStub.numericField(null, DISPLAY_TYPE_BLOCK));

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("should display the numeric field value", () => {
        const field = ReadonlyFieldStub.numericField(10.3334, DISPLAY_TYPE_BLOCK);
        const wrapper = getWrapper(field);
        const paragraph = wrapper.find("p");

        expect(paragraph.exists()).toBe(true);
        expect(paragraph.text()).toStrictEqual(String(field.value));
    });
});
