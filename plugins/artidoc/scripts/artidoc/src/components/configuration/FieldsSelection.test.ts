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

import { describe, it, expect, beforeEach } from "vitest";
import { createGettext } from "vue3-gettext";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldsSelection from "@/components/configuration/FieldsSelection.vue";
import type { ReadonlyField } from "@/sections/readonly-fields/ReadonlyFieldsCollection";

describe("FieldsSelection", () => {
    let selected_fields: ReadonlyField[];

    beforeEach(() => {
        selected_fields = [];
    });

    function getWrapper(): VueWrapper {
        return shallowMount(FieldsSelection, {
            props: {
                selected_fields,
            },
            global: { plugins: [createGettext({ silent: true })] },
        });
    }

    it("should display an empty table if no fields are selected", () => {
        const wrapper = getWrapper();
        const empty_state_table = wrapper.find("[data-test=readonly-fields-empty-state]");

        expect(empty_state_table.exists()).toBe(true);
    });

    it("should display a table with two fields if two fields are selected", () => {
        const label_1 = "First field";
        const label_2 = "Second field";
        selected_fields = [
            { label: label_1 } as ReadonlyField,
            { label: label_2 } as ReadonlyField,
        ];

        const wrapper = getWrapper();
        const readonly_fields = wrapper.findAll("[data-test=readonly-field-rows]");

        expect(readonly_fields.length).toBe(2);
        expect(readonly_fields[0].html()).toContain(label_1);
        expect(readonly_fields[1].html()).toContain(label_2);
    });
});
