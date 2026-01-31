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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import LabelForField from "./LabelForField.vue";
import type { BaseFieldStructure, StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

describe("LabelForField", () => {
    const getWrapper = (field: Partial<BaseFieldStructure>): VueWrapper =>
        shallowMount(LabelForField, {
            props: {
                field: {
                    field_id: 123,
                    name: "summary",
                    label: "Summary",
                    required: false,
                    ...field,
                } as StructureFields,
            },
        });

    it.each([[true], [false]])(
        "should display the label with required = %s",
        (required: boolean) => {
            const wrapper = getWrapper({
                label: "Summary",
                required,
            });

            expect(wrapper.text()).toContain("Summary");
            expect(wrapper.find("[data-test=required]").exists()).toBe(required);
        },
    );
});
