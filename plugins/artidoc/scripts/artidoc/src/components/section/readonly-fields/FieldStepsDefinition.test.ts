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
import { ReadonlyFieldStub } from "@/sections/stubs/ReadonlyFieldStub";
import type { ReadonlyFieldStepsDefinition } from "@/sections/readonly-fields/ReadonlyFields";
import FieldStepsDefinition from "@/components/section/readonly-fields/FieldStepsDefinition.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";

describe("FieldStepsDefinition", () => {
    const getWrapper = (field: ReadonlyFieldStepsDefinition): VueWrapper => {
        return shallowMount(FieldStepsDefinition, {
            props: {
                field,
            },
            global: {
                plugins: [createGettext({ silent: true }), VueDOMPurifyHTML],
            },
        });
    };

    it("When the field has no values, then it should display an empty state", () => {
        const wrapper = getWrapper(ReadonlyFieldStub.stepsDefinitionField([]));

        expect(wrapper.find("[data-test=empty-state]").exists()).toBe(true);
    });

    it("should display the steps definition field value", () => {
        const wrapper = getWrapper(
            ReadonlyFieldStub.stepsDefinitionField([
                { description: "First step", expected_results: "" },
                { description: "Second step", expected_results: "Everything works!" },
            ]),
        );

        const steps = wrapper.findAll("[data-test=step]");
        expect(steps).toHaveLength(2);
        expect(steps[0].find("[data-test=step-description]").text()).toContain("First step");
        expect(steps[0].find("[data-test=step-results]").exists()).toBe(false);

        expect(steps[1].find("[data-test=step-description]").text()).toContain("Second step");
        expect(steps[1].find("[data-test=step-results]").text()).toContain("Everything works!");
    });
});
