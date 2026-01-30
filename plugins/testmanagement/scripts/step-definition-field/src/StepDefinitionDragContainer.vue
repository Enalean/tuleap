<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div>
        <input
            v-if="hasNoStepRemaining"
            type="hidden"
            v-bind:name="'artifact[' + field_id + '][no_steps]'"
            value="1"
        />
        <template v-for="(step, index) in steps" v-bind:key="'add-button-' + step.uuid">
            <div
                class="ttm-definition-step-draggable"
                v-bind:draggable="is_dragging"
                v-on:dragstart.self="onDragStart($event, step, index)"
                v-on:dragover.prevent="onDragOver(step)"
                v-on:drop="onDrop(index)"
                v-on:dragenter.prevent
                v-on:dragend.prevent="onDragEnd"
                v-bind:class="getDragndropClasses(step, index)"
            >
                <step-definition-entry
                    v-bind:key="step.uuid"
                    v-bind:dynamic_rank="index + 1"
                    v-bind:step="step"
                />
                <div v-show="!is_dragging" class="ttm-definition-step-add-bar">
                    <button
                        type="button"
                        class="btn btn-primary"
                        v-on:click="addStep(index + 1)"
                        data-test="add-step"
                    >
                        <i class="fa-solid fa-plus"></i>
                        {{ $gettext("Add step") }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useMutations, useState } from "vuex-composition-helpers";
import StepDefinitionEntry from "./StepDefinitionEntry.vue";
import type { Step } from "./Step";

const { steps, is_dragging, field_id } = useState(["steps", "is_dragging", "field_id"]);
const { addStep, moveStep } = useMutations(["addStep", "moveStep"]);

const dragged_step = ref<Step | null>(null);
const hovered_step = ref<Step | null>(null);
const dragged_step_index = ref(0);

const hasNoStepRemaining = computed(
    () => steps.value.filter((step: Step) => step.is_deleted).length === steps.value.length,
);

function onDragEnd() {
    dragged_step.value = null;
    hovered_step.value = null;
    dragged_step_index.value = 0;
}

function onDragStart(event: DragEvent, step: Step, index: number) {
    if (!event.dataTransfer) {
        return;
    }

    event.dataTransfer.dropEffect = "move";
    event.dataTransfer.effectAllowed = "move";
    dragged_step_index.value = index;
    dragged_step.value = { ...step };
}

function onDrop(index: number) {
    moveStep([dragged_step.value, index]);
}

function onDragOver(step: Step) {
    if (step.uuid !== dragged_step.value?.uuid) {
        hovered_step.value = { ...step };
    } else {
        hovered_step.value = null;
    }
}

function getDragndropClasses(step: Step, index: number) {
    if (!hovered_step.value || hovered_step.value.uuid !== step.uuid) {
        return "";
    }

    if (index === dragged_step_index.value) {
        return "";
    }

    return (
        "ttm-definition-step-draggable-drop-" +
        (index < dragged_step_index.value ? "before" : "after")
    );
}
</script>
