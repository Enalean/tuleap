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
    <div>
        <div v-if="isThereAtLeastOneStep">
            <button
                v-if="areThereAtLeastTwoSteps"
                type="button"
                class="ttm-definition-reorder-steps-button btn btn-small"
                v-on:click="toggleIsDragging()"
            >
                <i class="fas fa-sync fa-rotate-90"></i>
                <translate v-if="is_dragging" key="stop-reordering">
                    Stop reordering steps
                </translate>
                <translate v-else key="start-reordering">Reorder steps</translate>
            </button>
            <div class="ttm-definition-step-add-bar" v-show="!is_dragging">
                <button type="button" class="btn btn-primary" v-on:click="addStep">
                    <i class="fa fa-plus"></i>
                    <translate>Add step</translate>
                </button>
            </div>
        </div>
        <step-definition-drag-container />
        <step-definition-no-step v-if="!isThereAtLeastOneStep" />
    </div>
</template>

<script>
import StepDefinitionNoStep from "./StepDefinitionNoStep.vue";
import StepDefinitionDragContainer from "./StepDefinitionDragContainer.vue";
import { mapState, mapMutations } from "vuex";

export default {
    name: "StepDefinitionField",
    components: { StepDefinitionNoStep, StepDefinitionDragContainer },
    props: {
        initial_steps: Array,
        artifact_field_id: Number,
        empty_step: Object,
        upload_url: String,
        upload_field_name: String,
        upload_max_size: String,
    },
    computed: {
        ...mapState(["steps", "field_id", "is_dragging"]),
        isThereAtLeastOneStep() {
            return this.steps.length !== 0;
        },
        areThereAtLeastTwoSteps() {
            return this.steps.length > 1;
        },
    },
    created() {
        this.$store.commit("initStepField", [
            this.initial_steps,
            this.artifact_field_id,
            this.empty_step,
            this.upload_url,
            this.upload_field_name,
            this.upload_max_size,
        ]);
    },
    destroyed() {
        window.removeEventListener("mousemove", this.replaceMirror);
    },
    methods: {
        ...mapMutations(["toggleIsDragging"]),
        addStep(index) {
            this.$store.commit("addStep", index);
        },
    },
};
</script>
