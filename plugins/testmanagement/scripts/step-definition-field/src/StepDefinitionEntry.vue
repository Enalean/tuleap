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
    <div class="ttm-definition-step" data-test="editable-step">
        <step-definition-draggable-component
            v-show="is_dragging"
            v-bind:step="reactive_step"
            v-bind:dynamic_rank="dynamic_rank"
        />
        <div v-show="!is_dragging" class="ttm-definition-step-rank ttm-execution-step-rank-edition">
            {{ dynamic_rank }}
        </div>
        <div v-show="!is_dragging" class="ttm-definition-step-description">
            <step-definition-marked-as-deleted v-if="step.is_deleted" v-bind:step="reactive_step" />
            <step-definition-editable-step
                v-if="!reactive_step.is_deleted"
                v-bind:step="reactive_step"
                v-on:update-description="updateDescription"
                v-on:update-expected-results="updateExpectedResults"
                v-on:toggle-rte="toggleRTE"
            />
        </div>
    </div>
</template>

<script>
import StepDefinitionMarkedAsDeleted from "./StepDefinitionMarkedAsDeleted.vue";
import StepDefinitionEditableStep from "./StepDefinitionEditableStep.vue";
import StepDefinitionDraggableComponent from "./StepDefinitionDraggableComponent.vue";
import { mapState } from "vuex";

export default {
    name: "StepDefinitionEntry",
    components: {
        StepDefinitionMarkedAsDeleted,
        StepDefinitionEditableStep,
        StepDefinitionDraggableComponent,
    },
    props: {
        step: {
            type: Object,
            required: true,
        },
        dynamic_rank: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            reactive_step: this.step,
        };
    },
    computed: {
        ...mapState(["is_dragging"]),
    },
    methods: {
        updateDescription(event) {
            this.reactive_step.raw_description = event.target.value;
        },
        updateExpectedResults(event) {
            this.reactive_step.raw_expected_results = event.target.value;
        },
        toggleRTE(event) {
            this.reactive_step.description_format = event.target.value;
        },
    },
};
</script>
