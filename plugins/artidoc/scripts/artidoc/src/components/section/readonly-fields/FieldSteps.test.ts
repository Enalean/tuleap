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
import type {
    ReadonlyFieldStepsDefinition,
    ReadonlyFieldStepsExecution,
} from "@/sections/readonly-fields/ReadonlyFields";
import {
    STEP_BLOCKED,
    STEP_FAILED,
    STEP_NOT_RUN,
    STEP_PASSED,
} from "@/sections/readonly-fields/ReadonlyFields";
import FieldSteps from "@/components/section/readonly-fields/FieldSteps.vue";
import VueDOMPurifyHTML from "vue-dompurify-html";

describe("FieldSteps", () => {
    const getWrapper = (
        field: ReadonlyFieldStepsDefinition | ReadonlyFieldStepsExecution,
    ): VueWrapper => {
        return shallowMount(FieldSteps, {
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

    it("should display the steps executions status badges", () => {
        const wrapper = getWrapper(
            ReadonlyFieldStub.stepsExecutionField([
                {
                    description: "Successful step",
                    expected_results: "Great success!",
                    status: STEP_PASSED,
                },
                {
                    description: "Waiting step",
                    expected_results: "Wait for something to succeed",
                    status: STEP_BLOCKED,
                },
                {
                    description: "Failing step",
                    expected_results: "Great failure!",
                    status: STEP_FAILED,
                },
                {
                    description: "Not run step",
                    expected_results: "Not run yet",
                    status: STEP_NOT_RUN,
                },
            ]),
        );

        const steps = wrapper.findAll("[data-test=step]");

        expect(steps).toHaveLength(4);

        const [successful_step, blocked_step, failing_step, notrun_step] = steps;

        const success_badge = successful_step.find("[data-test=execution-status]");
        expect(success_badge.exists()).toBe(true);
        expect(success_badge.text()).toBe("Passed");
        expect(success_badge.classes()).toContain("tlp-badge-success");

        const blocked_badge = blocked_step.find("[data-test=execution-status]");
        expect(blocked_badge.exists()).toBe(true);
        expect(blocked_badge.text()).toBe("Blocked");
        expect(blocked_badge.classes()).toContain("tlp-badge-info");

        const failure_badge = failing_step.find("[data-test=execution-status]");
        expect(failure_badge.exists()).toBe(true);
        expect(failure_badge.text()).toBe("Failed");
        expect(failure_badge.classes()).toContain("tlp-badge-danger");

        const notrun_badge = notrun_step.find("[data-test=execution-status]");
        expect(notrun_badge.exists()).toBe(true);
        expect(notrun_badge.text()).toBe("Not run");
        expect(notrun_badge.classes()).toContain("tlp-badge-secondary");
    });
});
