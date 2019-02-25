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
    <td
        class="tracker-workflow-transition-cell"
        v-bind:class="{
            'tracker-workflow-transition-action-updated': is_transition_updated
        }"
    >
        <i
            v-if="!is_allowed"
            class="fa fa-ban tracker-workflow-transition-cell-forbidden"
            data-test-type="forbidden-transition"
        ></i>
        <i
            v-else-if="is_operation_running"
            class="fa fa-circle-o-notch fa-spin tracker-workflow-transition-spinner"
            data-test-type="spinner"
        ></i>
        <div
            v-else-if="is_empty"
            class="tracker-workflow-transition-cell-empty"
            v-bind:class="{
                'tracker-workflow-transition-action-disabled': is_another_operation_running
            }"
            v-on:click="is_another_operation_running || createTransition()"
            data-test-action="create-transition"
        ></div>
        <template v-else-if="transition && is_workflow_advanced">
            <div
                class="tracker-workflow-transition-mark"
                v-bind:class="{
                    'tracker-workflow-transition-action-disabled': is_another_operation_running,
                    'tracker-workflow-transition-action-updated': is_transition_updated
                }"
                ref="transition_mark"
                data-placement="top-start"
                data-trigger="click"
                data-test-action="confirm-delete-transition"
            >
                ⤴
            </div>
            <transition-delete-popover v-bind:delete-transition="deleteTransition"/>
            <button
                type="button"
                class="tlp-button-primary tlp-button-mini tracker-workflow-advanced-transition-button"
                v-bind:class="{ 'tlp-button-success': is_transition_updated }"
                v-on:click="openModal()"
                data-test-action="configure-transition"
                v-translate
            >
                Configure
            </button>
        </template>
        <div
            v-else-if="transition && !is_workflow_advanced"
            class="tracker-workflow-transition-mark"
            v-bind:class="{
                'tracker-workflow-transition-action-disabled': is_another_operation_running
            }"
            v-on:click="is_another_operation_running || deleteTransition()"
            data-test-action="delete-transition"
        >
            ⤴
        </div>
    </td>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import { createPopover } from "tlp";
import TransitionDeletePopover from "./TransitionDeletePopover.vue";

export default {
    name: "TransitionMatrixContent",
    components: { TransitionDeletePopover },
    props: {
        from: {
            type: Object,
            required: true
        },
        to: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            is_operation_running: false,
            popover: null
        };
    },
    computed: {
        ...mapState({
            is_another_operation_running: "is_operation_running"
        }),
        ...mapGetters(["is_workflow_advanced", "current_workflow_transitions"]),
        transition() {
            return this.current_workflow_transitions.find(
                transition => transition.from_id === this.from.id && transition.to_id === this.to.id
            );
        },
        is_allowed() {
            return this.from.id !== this.to.id;
        },
        is_empty() {
            return this.is_allowed && !this.transition;
        },
        is_transition_updated() {
            if (!this.transition || !this.is_workflow_advanced) {
                return false;
            }

            return this.transition.updated;
        }
    },
    mounted() {
        if (this.transition) {
            this.createPopover();
        }
    },
    beforeDestroy() {
        this.destroyPopoverIfExists();
    },
    methods: {
        async createTransition() {
            this.is_operation_running = true;
            const new_transition = {
                from_id: this.from.id,
                to_id: this.to.id
            };
            try {
                await this.$store.dispatch("createTransition", new_transition);
                this.createPopover();
            } finally {
                this.is_operation_running = false;
            }
        },
        async deleteTransition() {
            this.is_operation_running = true;
            try {
                await this.$store.dispatch("deleteTransition", this.transition);
                this.destroyPopoverIfExists();
            } finally {
                this.is_operation_running = false;
            }
        },
        openModal() {
            this.$store.dispatch(
                "transitionModal/showTransitionConfigurationModal",
                this.transition
            );
        },
        async createPopover() {
            await this.$nextTick();
            if (this.popover === null && this.$refs.transition_mark && this.$refs.popover) {
                this.popover = createPopover(this.$refs.transition_mark, this.$refs.popover);
            }
        },
        destroyPopoverIfExists() {
            if (this.popover !== null) {
                this.popover.destroy();
                this.popover = null;
            }
        }
    }
};
</script>
