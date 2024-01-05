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
    <div
        class="tlp-modal tlp-modal-warning"
        role="dialog"
        aria-labelledby="modal-confirm-workflow-mode-change-label"
        ref="modal"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-confirm-workflow-mode-change-label">
                {{ $gettext("Wait a minute...") }}
            </h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fas fa-times tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-body">
            <p v-if="is_workflow_advanced" key="simple_text">
                {{
                    $gettext(
                        "You're about to switch to simple configuration mode. The first configuration in the destination state column will be applied to the whole state. Please check that each state configuration is correct.",
                    )
                }}
            </p>
            <p v-else key="advanced_text">
                {{
                    $gettext(
                        "You're about to switch to advanced configuration mode. Each transition will be configurable independently. They will copy their state configuration during the switch.",
                    )
                }}
            </p>
            <p>{{ $gettext("Please confirm your action.") }}</p>
            <p class="tlp-alert-danger" v-if="!is_workflow_advanced" key="warning_switch">
                {{
                    $gettext(
                        'If you have any post actions of type "Frozen Fields" or "Hidden Fieldsets", they will be deleted.',
                    )
                }}
            </p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-warning tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-warning tlp-modal-action"
                v-on:click="confirm()"
                v-bind:disabled="is_operation_running"
                data-test="button-switch-to-simple-configuration"
            >
                <i
                    v-if="is_operation_running"
                    class="tlp-button-icon fas fa-circle-notch fa-spin"
                ></i>
                {{ button_label }}
            </button>
        </div>
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";

export default {
    name: "WorkflowModeSwitchModal",
    computed: {
        ...mapState(["is_operation_running"]),
        ...mapGetters(["is_workflow_advanced"]),
        button_label() {
            return this.is_workflow_advanced
                ? this.$gettext("Switch to simple configuration")
                : this.$gettext("Switch to advanced configuration");
        },
    },
    data() {
        return {
            modal: null,
        };
    },
    mounted() {
        this.modal = createModal(this.$refs.modal, { destroy_on_hide: true });
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.modal.show();
    },
    beforeDestroy() {
        this.modal = null;
    },
    methods: {
        async confirm() {
            await this.$store.dispatch("changeWorkflowMode", !this.is_workflow_advanced);
            this.modal.hide();
        },
        reset() {
            this.$emit("close-modal");
        },
    },
};
</script>
