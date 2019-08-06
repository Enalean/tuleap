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
    <div class="ttm-definition-step">
        <div class="ttm-definition-step-rank ttm-execution-step-rank-edition">{{ dynamic_rank }}</div>
        <div class="ttm-definition-step-description">
            <step-definition-marked-as-deleted
                v-show="is_marked_as_deleted"
                v-bind:step="step"
                v-on:unmarkDeletion="unmarkDeletion"
            />
            <step-definition-editable-step
                v-show="! is_marked_as_deleted"
                v-bind:step="step"
                v-on:markAsDeleted="markAsDeleted"
                v-on:removeDeletedStepsOnFormSubmission="removeDeletedStepsOnFormSubmission"
            />
        </div>
    </div>
</template>

<script>
import StepDefinitionMarkedAsDeleted from "./StepDefinitionMarkedAsDeleted.vue";
import StepDefinitionEditableStep from "./StepDefinitionEditableStep.vue";

export default {
    name: "StepDefinitionEntry",
    components: { StepDefinitionMarkedAsDeleted, StepDefinitionEditableStep },
    props: {
        step: Object,
        dynamic_rank: Number
    },
    data() {
        return {
            is_marked_as_deleted: false
        };
    },
    methods: {
        markAsDeleted() {
            if (this.step.raw_description.length === 0) {
                this.$store.commit("deleteStep", this.step);
            } else {
                this.is_marked_as_deleted = true;
            }
        },
        addStep(index) {
            this.$store.commit("addStep", index);
        },
        unmarkDeletion() {
            this.is_marked_as_deleted = false;
        },
        removeDeletedStepsOnFormSubmission(description_form) {
            description_form.addEventListener("submit", () => {
                if (this.is_marked_as_deleted) {
                    this.$store.commit("deleteStep", this.step);
                }
            });
        }
    }
};
</script>
