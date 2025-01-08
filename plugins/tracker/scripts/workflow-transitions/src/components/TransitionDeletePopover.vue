<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
            class="tracker-workflow-transition-mark"
            v-bind:class="{
                'tracker-workflow-transition-action-disabled': is_operation_running,
                'tracker-workflow-transition-action-updated': is_transition_updated,
            }"
            ref="transition_mark"
            data-placement="top-start"
            data-trigger="click"
            data-test-action="confirm-delete-transition"
        >
            <i class="fas fa-level-up-alt"></i>
        </div>

        <section class="tlp-popover tlp-popover-danger" ref="popover">
            <div class="tlp-popover-arrow"></div>
            <div class="tlp-popover-header">
                <h1 class="tlp-popover-title">{{ $gettext("Confirm deleting the transition") }}</h1>
            </div>
            <div class="tlp-popover-body">
                <p v-if="isWorkflowAdvanced" key="advanced-text">
                    {{
                        $gettext(
                            "Are you sure you want to delete this transition? Its configuration will be lost and cannot be recovered.",
                        )
                    }}
                </p>
                <p v-else key="simple-text">
                    {{
                        $gettext(
                            "Are you sure you want to delete this transition? Since it is the last transition for this state, deleting it will also delete the state's configuration. It cannot be recovered.",
                        )
                    }}
                </p>
            </div>
            <div class="tlp-popover-footer">
                <button
                    type="button"
                    class="tlp-button-danger tlp-button-outline"
                    data-dismiss="popover"
                >
                    {{ $gettext("Cancel") }}
                </button>
                <button
                    type="button"
                    class="tlp-button-danger"
                    v-on:click="deleteTransition()"
                    data-dismiss="popover"
                >
                    <i class="far fa-trash-alt tlp-button-icon"></i>
                    {{ $gettext("Delete transition") }}
                </button>
            </div>
        </section>
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import { createPopover } from "@tuleap/tlp-popovers";

export default {
    name: "TransitionDeletePopover",
    props: {
        is_transition_updated: {
            type: Boolean,
            required: true,
        },
        is_confirmation_needed: {
            type: Boolean,
            required: true,
        },
    },
    data() {
        return {
            popover: null,
        };
    },
    ...mapGetters(["is_workflow_advanced"]),
    computed: {
        ...mapState(["is_operation_running"]),
        ...mapGetters(["current_workflow_transitions", "is_workflow_advanced"]),
    },
    mounted() {
        if (this.is_confirmation_needed === true) {
            this.createPopoverIfNotExists();
        }
    },
    unmounted() {
        this.destroyPopoverIfExists();
    },
    methods: {
        deleteTransition() {
            this.$emit("deleteTransition");
        },
        isWorkflowAdvanced() {
            return this.is_workflow_advanced;
        },
        async createPopoverIfNotExists() {
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
        },
    },
};
</script>
