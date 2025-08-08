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
    <div class="steps">
        <div v-for="(step, rank) in field.value" v-bind:key="rank" class="step" data-test="step">
            <span class="step-rank">{{ rank + 1 }}</span>
            <div class="step-definition">
                <p
                    v-dompurify-html="step.description"
                    class="step-description"
                    data-test="step-description"
                ></p>
                <section
                    v-if="step.expected_results !== ''"
                    class="step-results"
                    data-test="step-results"
                >
                    <step-definition-arrow />
                    <p v-dompurify-html="step.expected_results"></p>
                </section>
            </div>
        </div>
    </div>
    <p v-if="field.value.length === 0" class="tlp-property-empty" data-test="empty-state">
        {{ $gettext("Empty") }}
    </p>
</template>

<script setup lang="ts">
import { useGettext } from "vue3-gettext";
import type { ReadonlyFieldStepsDefinition } from "@/sections/readonly-fields/ReadonlyFields";
import StepDefinitionArrow from "@/components/section/readonly-fields/StepDefinitionArrow.vue";

const { $gettext } = useGettext();
defineProps<{
    field: ReadonlyFieldStepsDefinition;
}>();
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
}

.step-description {
    margin: unset;
}

.step-results {
    display: flex;
    align-items: baseline;
    gap: 5px;
}
</style>
