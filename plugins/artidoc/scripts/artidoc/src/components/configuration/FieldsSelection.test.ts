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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { createGettext } from "vue3-gettext";
import * as list_picker from "@tuleap/list-picker";
import type { ListPicker } from "@tuleap/list-picker";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import FieldsSelection from "@/components/configuration/FieldsSelection.vue";
import type { ConfigurationField } from "@/sections/readonly-fields/AvailableReadonlyFields";
import SelectedFieldsList from "@/components/configuration/SelectedFieldsList.vue";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";

const field_1 = ConfigurationFieldStub.withLabel("Field 1");
const field_2 = ConfigurationFieldStub.withLabel("Field 2");

describe("FieldsSelection", () => {
    let available_fields: ConfigurationField[];
    let selected_fields: ConfigurationField[];
    let list_picker_instance: ListPicker;

    beforeEach(() => {
        available_fields = [field_1, field_2];
        selected_fields = [];

        list_picker_instance = {
            destroy: vi.fn(),
        };
        vi.spyOn(list_picker, "createListPicker").mockReturnValue(list_picker_instance);
    });

    function getWrapper(): VueWrapper {
        return shallowMount(FieldsSelection, {
            props: {
                selected_fields,
                available_fields,
            },
            global: { plugins: [createGettext({ silent: true })] },
        });
    }

    it("should display an empty state when no fields are selected", () => {
        const wrapper = getWrapper();
        const empty_state = wrapper.find("[data-test=readonly-fields-empty-state]");

        expect(empty_state.exists()).toBe(true);
    });

    it("should display the selected fields", () => {
        selected_fields = [field_1, field_2];

        const wrapper = getWrapper();
        expect(wrapper.findComponent(SelectedFieldsList).exists()).toBe(true);
    });

    it("should display the available fields in the list picker", () => {
        const wrapper = getWrapper();
        const available_fields = wrapper.findAll("[data-test=available-readonly-fields]");

        expect(available_fields.length).toBe(2);
        expect(available_fields[0].html()).toContain(field_1.label);
        expect(available_fields[1].html()).toContain(field_2.label);
    });
});
