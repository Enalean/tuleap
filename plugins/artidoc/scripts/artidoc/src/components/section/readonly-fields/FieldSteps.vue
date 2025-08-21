<!--
  - Copyright (c) Enalean, 2025 - present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <label class="tlp-label document-label">{{ field.label }}</label>
    <div class="steps document-steps">
        <div
            v-for="(step, rank) in field.value"
            v-bind:key="rank"
            class="step document-step"
            data-test="step"
        >
            <span class="step-rank document-step-rank">{{ rank + 1 }}</span>
            <div class="step-definition document-step-definition">
                <div
                    v-dompurify-html="step.description"
                    class="step-description document-step-description"
                    data-test="step-description"
                ></div>
                <section
                    v-if="step.expected_results !== ''"
                    class="step-results document-step-results"
                    data-test="step-results"
                >
                    <step-definition-arrow />
                    {{ $gettext("Expected results") }}
                    <div
                        class="step-expected document-step-expected"
                        v-dompurify-html="step.expected_results"
                    ></div>
                </section>
                <span
                    v-if="'status' in step"
                    class="step-execution-status"
                    v-bind:class="getStatusBadgeClasses(step.status)"
                    data-test="execution-status"
                    >{{ getBadgeLabel(step.status) }}</span
                >
            </div>
        </div>
    </div>
    <p v-if="field.value.length === 0" class="tlp-property-empty" data-test="empty-state">
        {{ $gettext("Empty") }}
    </p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type {
    ReadonlyFieldStepsDefinition,
    ReadonlyFieldStepsExecution,
    StepExecutionStatus,
} from "@/sections/readonly-fields/ReadonlyFields";
import {
    STEP_BLOCKED,
    STEP_FAILED,
    STEP_NOT_RUN,
    STEP_PASSED,
} from "@/sections/readonly-fields/ReadonlyFields";
import StepDefinitionArrow from "@/components/section/readonly-fields/StepDefinitionArrow.vue";

const { $gettext } = useGettext();
defineProps<{
    field: ReadonlyFieldStepsDefinition | ReadonlyFieldStepsExecution;
}>();

function getStatusBadgeClasses(status: StepExecutionStatus): string {
    let badge_class = "";

    if (status === STEP_NOT_RUN) {
        return "tlp-badge-outline document-badge-outline tlp-badge-secondary";
    }

    switch (status) {
        case STEP_BLOCKED:
            badge_class = "tlp-badge-info document-badge-info";
            break;
        case STEP_PASSED:
            badge_class = "tlp-badge-success document-badge-success";
            break;
        case STEP_FAILED:
            badge_class = "tlp-badge-danger document-badge-danger";
            break;
        default:
            break;
    }
    return `document-badge ${badge_class}`;
}

function getBadgeLabel(status: StepExecutionStatus): string {
    switch (status) {
        case STEP_NOT_RUN:
            return $gettext("Not run");
        case STEP_BLOCKED:
            return $gettext("Blocked");
        case STEP_PASSED:
            return $gettext("Passed");
        case STEP_FAILED:
            return $gettext("Failed");
        default:
            return "";
    }
}
</script>

<style scoped lang="scss">
.steps {
    display: flex;
    flex-direction: column;
    gap: var(--tlp-small-spacing);
}

.step {
    display: flex;
    align-items: flex-start;
    font-size: 14px;
    line-height: 20px;
    gap: var(--tlp-small-spacing);
}

.step-rank {
    padding: 0 6px;
    border: 1px solid var(--tlp-main-color);
    border-radius: 10px;
    color: var(--tlp-main-color);
}

.step-definition {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 5px 0;
}

.step-description {
    margin: unset;
}

.step-results {
    display: grid;
    grid:
        "icon label" auto
        ". expect" auto / 16px auto;
    gap: 5px;
}

.step-arrow {
    align-self: center;
}

.step-expected {
    grid-area: expect;
}
</style>
