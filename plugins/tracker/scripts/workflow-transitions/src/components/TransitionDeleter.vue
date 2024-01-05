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
        <div
            v-if="!is_confirmation_needed"
            class="tracker-workflow-transition-mark"
            v-bind:class="{
                'tracker-workflow-transition-action-disabled': is_operation_running,
            }"
            v-on:click="deleteTransitionIfNothingElseIsRunning()"
            data-test="delete-transition-without-confirmation"
        >
            <i class="fas fa-level-up-alt"></i>
        </div>
        <transition-delete-popover
            v-else
            data-test="delete-transition"
            v-on:deleteTransition="deleteTransitionIfNothingElseIsRunning"
            v-bind:is_transition_updated="is_transition_updated"
            v-bind:is_confirmation_needed="is_confirmation_needed"
        />
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import TransitionDeletePopover from "./TransitionDeletePopover.vue";

export default {
    name: "TransitionDeleter",
    components: { TransitionDeletePopover },
    props: {
        transition: {
            type: Object,
            required: true,
        },
        deleteTransition: {
            type: Function,
            required: true,
        },
        is_transition_updated: {
            type: Boolean,
            required: true,
        },
    },
    computed: {
        ...mapState(["is_operation_running"]),
        ...mapGetters(["current_workflow_transitions", "is_workflow_advanced"]),
        is_confirmation_needed() {
            return (
                this.is_workflow_advanced ||
                (!this.is_workflow_advanced && this.is_last_transition_of_column)
            );
        },
        transitions_of_the_same_column() {
            return this.current_workflow_transitions.filter(
                (transition) => transition.to_id === this.transition.to_id,
            );
        },
        is_last_transition_of_column() {
            return (
                this.transitions_of_the_same_column.length === 1 &&
                this.transitions_of_the_same_column[0].id === this.transition.id
            );
        },
    },
    methods: {
        deleteTransitionIfNothingElseIsRunning() {
            if (this.is_operation_running) {
                return;
            }
            this.deleteTransition();
        },
    },
};
</script>
