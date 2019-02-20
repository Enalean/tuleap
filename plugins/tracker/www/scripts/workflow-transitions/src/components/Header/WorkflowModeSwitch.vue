<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
            class="tlp-form-element tracker-workflow-transition-configuration-form-item"
            v-bind:class="{ 'tlp-form-element-disabled': is_operation_running }"
        >
            <label
                class="tlp-label"
                for="workflow-advanced-configuration"
                v-on:click.prevent="showModal()"
            >
                <translate>Use advanced configuration</translate>
                <span
                    class="tlp-tooltip tlp-tooltip-top"
                    v-bind:data-tlp-tooltip="advanced_configuration_tooltip"
                >
                    <i class="fa fa-question-circle"></i>
                </span>
            </label>
            <div
                class="tlp-switch"
                v-bind:class="{ 'tlp-form-element-disabled': is_operation_running }"
            >
                <input
                    type="checkbox"
                    id="workflow-advanced-configuration"
                    class="tlp-switch-checkbox"
                    v-bind:checked="is_workflow_advanced"
                    v-bind:disabled="is_workflow_advanced || is_operation_running"
                >
                <label
                    class="tlp-switch-button"
                    for="workflow-advanced-configuration"
                    v-on:click.prevent="showModal()"
                    aria-hidden=""
                ></label>
            </div>
        </div>
        <div
            class="tlp-modal tlp-modal-warning"
            role="dialog"
            aria-labelledyby="modal-confirm-workflow-mode-change-label"
            ref="modal"
        >
            <div class="tlp-modal-header">
                <h1
                    class="tlp-modal-title"
                    id="modal-confirm-workflow-mode-change-label"
                    v-translate
                >
                    Wait a minute...
                </h1>
                <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">&times;</div>
            </div>
            <div class="tlp-modal-body">
                <p v-translate>You're about to switch to advanced configuration mode.</p>
                <p v-translate>Please confirm your action.</p>
            </div>
            <div class="tlp-modal-footer">
                <button
                    type="button"
                    class="tlp-button-warning tlp-button-outline tlp-modal-action"
                    data-dismiss="modal"
                    v-translate
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="tlp-button-warning tlp-modal-action"
                    v-on:click="confirm()"
                    v-bind:disabled="is_workflow_mode_change_running"
                >
                    <i
                        v-if="is_workflow_mode_change_running"
                        class="tlp-button-icon fa fa-circle-o-notch fa-spin"
                    ></i>
                    <translate>Switch to advanced configuration</translate>
                </button>
            </div>
        </div>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import { modal as createModal } from "tlp";

export default {
    name: "WorkflowModeSwitch",
    data() {
        return {
            modal: null
        };
    },
    computed: {
        ...mapState(["is_operation_running", "is_workflow_mode_change_running"]),
        ...mapGetters(["is_workflow_advanced"]),
        advanced_configuration_tooltip() {
            return this.$gettext(
                "Use advanced configuration if you want to configure each transition independently."
            );
        }
    },
    mounted() {
        this.modal = createModal(this.$refs.modal);
    },
    methods: {
        showModal() {
            if (this.is_workflow_advanced) {
                return;
            }
            this.modal.show();
        },
        async confirm() {
            await this.$store.dispatch("changeWorkflowMode", !this.is_workflow_advanced);
            this.modal.hide();
        }
    }
};
</script>
