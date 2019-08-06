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
        <button type="button" class="btn" v-on:click="addStep(0)">
            <i class="fa fa-plus"></i> <translate>Add step</translate>
        </button>
        <template v-for="(step, index) in steps">
            <step-definition-entry
                v-bind:key="step.uuid"
                v-bind:dynamic_rank="index + 1"
                v-bind:step="step"
            />
            <button type="button" class="btn" v-on:click="addStep(index + 1)" v-bind:key="'add-button-' + step.uuid">
                <i class="fa fa-plus"></i>
                <translate>Add step</translate>
            </button>
        </template>
        <p v-if="! isThereAtLeastOneStep">
            <input
                type="hidden"
                v-bind:name="'artifact[' + field_id + '][no_steps]'"
                value="1"
            >
            <translate>There isn't any step defined yet. Start by adding one.</translate>
        </p>
    </div>
</template>

<script>
import StepDefinitionEntry from "./StepDefinitionEntry.vue";
import { mapState } from "vuex";
export default {
    name: "StepDefinitionField",
    components: { StepDefinitionEntry },
    props: {
        initial_steps: Array,
        artifact_field_id: Number,
        empty_step: Object
    },
    computed: {
        ...mapState(["steps", "field_id"]),
        isThereAtLeastOneStep() {
            return this.steps.length !== 0;
        }
    },
    created() {
        this.$store.commit(
            "initStepField",
            [this.initial_steps,
            this.artifact_field_id,
            this.empty_step]
        );
    },
    methods: {
        addStep(index) {
            this.$store.commit("addStep", index);
        }
    }
};
</script>
