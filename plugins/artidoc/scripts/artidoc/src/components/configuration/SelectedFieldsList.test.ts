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
import { ref } from "vue";
import { createGettext } from "vue3-gettext";
import { shallowMount } from "@vue/test-utils";
import SelectedFieldsList from "@/components/configuration/SelectedFieldsList.vue";
import { buildFieldsReorderer } from "@/sections/readonly-fields/FieldsReorderer";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";

describe("SelectedFieldsList", () => {
    it("should display the selected fields", () => {
        const selected_fields = [
            ConfigurationFieldStub.withLabel("Field 1"),
            ConfigurationFieldStub.withLabel("Field 2"),
        ];

        const wrapper = shallowMount(SelectedFieldsList, {
            props: {
                currently_selected_fields: selected_fields,
                fields_reorderer: buildFieldsReorderer(ref(selected_fields)),
            },
            global: { plugins: [createGettext({ silent: true })] },
        });

        const readonly_fields = wrapper.findAll("[data-test=readonly-field-rows]");

        expect(readonly_fields.length).toBe(2);
        expect(readonly_fields[0].html()).toContain(selected_fields[0].label);
        expect(readonly_fields[1].html()).toContain(selected_fields[1].label);
    });
});
