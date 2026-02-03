<!--
  - Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
    <div data-test="step-definition-field">
        <div v-if="isThereAtLeastOneStep">
            <button
                v-if="areThereAtLeastTwoSteps"
                type="button"
                class="ttm-definition-reorder-steps-button btn btn-small"
                v-on:click="toggleIsDragging"
            >
                <i class="fas fa-sync fa-rotate-90"></i>
                <span v-if="is_dragging" key="stop-reordering">
                    {{ $gettext("Stop reordering steps") }}
                </span>
                <span v-else key="start-reordering">{{ $gettext("Reorder steps") }}</span>
            </button>
            <div class="ttm-definition-step-add-bar" v-show="!is_dragging">
                <button
                    type="button"
                    class="btn btn-primary"
                    v-on:click="addStep([0, empty_step])"
                    data-test="add-step"
                >
                    <i class="fa-solid fa-plus"></i>
                    {{ $gettext("Add step") }}
                </button>
            </div>
        </div>
        <step-definition-drag-container />
        <step-definition-no-step v-if="!isThereAtLeastOneStep" />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount } from "vue";
import { useStore, useState, useMutations } from "vuex-composition-helpers";
import { strictInject } from "@tuleap/vue-strict-inject";
import StepDefinitionNoStep from "./StepDefinitionNoStep.vue";
import StepDefinitionDragContainer from "./StepDefinitionDragContainer.vue";
import type { Step } from "./Step";
import { EMPTY_STEP, IS_DRAGGING } from "./injection-keys";

const empty_step = strictInject(EMPTY_STEP);
const is_dragging = strictInject(IS_DRAGGING);

const { steps } = useState(["steps"]);
const { addStep } = useMutations(["addStep"]);

const props = defineProps<{
    initial_steps: Array<Step>;
}>();

const isThereAtLeastOneStep = computed(() => steps.value.length !== 0);
const areThereAtLeastTwoSteps = computed(() => steps.value.length > 1);

onBeforeMount(() => {
    useStore().commit("initStepField", props.initial_steps);
});

function toggleIsDragging() {
    is_dragging.value = !is_dragging.value;
}
</script>
