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
import type { VueWrapper } from "@vue/test-utils";
import SelectedFieldsList from "@/components/configuration/SelectedFieldsList.vue";
import { buildFieldsReorderer } from "@/sections/readonly-fields/FieldsReorderer";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import {
    DISPLAY_TYPE_BLOCK,
    DISPLAY_TYPE_COLUMN,
} from "@/sections/readonly-fields/AvailableReadonlyFields";

describe("SelectedFieldsList", () => {
    const getWrapper = (selected_fields: ConfigurationField[]): VueWrapper =>
        shallowMount(SelectedFieldsList, {
            props: {
                currently_selected_fields: selected_fields,
                fields_reorderer: buildFieldsReorderer(ref(selected_fields)),
            },
            global: { plugins: [createGettext({ silent: true })] },
        });

    it("should display the selected fields", () => {
        const selected_fields = [
            ConfigurationFieldStub.withLabel("Field 1"),
            ConfigurationFieldStub.withLabel("Field 2"),
        ];
        const readonly_fields = getWrapper(selected_fields).findAll(
            "[data-test=readonly-field-rows]",
        );

        expect(readonly_fields.length).toBe(2);
        expect(readonly_fields[0].html()).toContain(selected_fields[0].label);
        expect(readonly_fields[1].html()).toContain(selected_fields[1].label);
    });

    it("When the user checks/unchecks the 'full row' checkbox, then it should update the field", async () => {
        const field = ConfigurationFieldStub.withLabel("My string field");
        const wrapper = getWrapper([field]);
        const switch_display_type_checkbox = wrapper.find<HTMLInputElement>(
            "[data-test=switch-display-type-checkbox]",
        );

        await switch_display_type_checkbox.setValue(); // Check the checkbox
        expect(field.display_type).toBe(DISPLAY_TYPE_BLOCK);

        await switch_display_type_checkbox.setValue(1); // Uncheck the checkbox
        expect(field.display_type).toBe(DISPLAY_TYPE_COLUMN);
    });

    it("When the display type cannot be changed, then the 'full row' checkbox should be disabled", () => {
        const field = ConfigurationFieldStub.withFixedDisplayType();
        const switch_display_type_checkbox = getWrapper([field]).find<HTMLInputElement>(
            "[data-test=switch-display-type-checkbox]",
        );

        expect(switch_display_type_checkbox.attributes("disabled")).toBeDefined();
    });
});
