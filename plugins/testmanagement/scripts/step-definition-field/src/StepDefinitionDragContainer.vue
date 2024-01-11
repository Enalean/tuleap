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
        <template v-for="(step, index) in steps">
            <div
                class="ttm-definition-step-draggable"
                v-bind:key="'add-button-' + step.uuid"
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
                        <i class="fa fa-plus"></i>
                        <translate>Add step</translate>
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import StepDefinitionEntry from "./StepDefinitionEntry.vue";
import { mapState, mapMutations } from "vuex";

export default {
    name: "StepDefinitionDragContainer",
    components: { StepDefinitionEntry },
    data() {
        return {
            dragged_step: null,
            hovered_step: null,
            dragged_step_index: 0,
        };
    },
    computed: {
        ...mapState(["steps", "is_dragging", "field_id"]),
        hasNoStepRemaining() {
            return (
                this.steps.filter((step) => step.is_deleted === true).length === this.steps.length
            );
        },
    },
    methods: {
        ...mapMutations(["addStep", "moveStep"]),
        onDragEnd() {
            this.dragged_step = null;
            this.hovered_step = null;
            this.dragged_step_index = 0;
        },
        onDragStart(event, step, index) {
            event.dataTransfer.dropEffect = "move";
            event.dataTransfer.effectAllowed = "move";
            this.dragged_step_index = index;
            this.dragged_step = { ...step };
        },
        onDrop(index) {
            this.moveStep([this.dragged_step, index]);
        },
        onDragOver(step) {
            if (step.uuid !== this.dragged_step.uuid) {
                this.hovered_step = { ...step };
            } else {
                this.hovered_step = null;
            }
        },
        getDragndropClasses(step, index) {
            if (!this.hovered_step || this.hovered_step.uuid !== step.uuid) {
                return "";
            }

            if (index === this.dragged_step_index) {
                return "";
            }

            return (
                "ttm-definition-step-draggable-drop-" +
                (index < this.dragged_step_index ? "before" : "after")
            );
        },
    },
};
</script>
