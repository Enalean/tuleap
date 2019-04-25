<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
        <step-definition-entry
            v-for="(step, index) in steps"
            v-bind:key="step.uuid"
            v-bind:dynamic-rank="index + 1"
            v-bind:step="step"
            v-bind:field-id="fieldId"
            v-bind:delete-step="deleteStep"
        />
        <p v-if="! isThereAtLeastOneStep">
            <input
                type="hidden"
                v-bind:name="'artifact[' + fieldId + '][no_steps]'"
                value="1"
            >
            <translate>There isn't any step defined yet. Start by adding one.</translate>
        </p>
        <button
            type="button"
            class="btn"
            v-on:click="addStep"
        >
            <i class="fa fa-plus"></i> <translate>Add step</translate>
        </button>
    </div>
</template>

<script>
import StepDefinitionEntry from "./StepDefinitionEntry.vue";
import uuid from "uuid/v4";

export default {
    name: "StepDefinitionField",
    components: { StepDefinitionEntry },
    props: {
        steps: Array,
        fieldId: Number,
        emptyStep: Object
    },
    computed: {
        isThereAtLeastOneStep() {
            return this.steps.length !== 0;
        }
    },
    created() {
        for (const step of this.steps) {
            step.uuid = uuid();
        }
    },
    methods: {
        deleteStep(step) {
            const index = this.steps.indexOf(step);
            if (index > -1) {
                this.steps.splice(index, 1);
            }
        },
        addStep() {
            const step = Object.assign({}, this.emptyStep);
            step.uuid = uuid();

            this.steps.push(step);
        }
    }
};
</script>
