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
            class="tlp-form-element tracker-workflow-transition-configuration-form-item"
            v-bind:class="{ 'tlp-form-element-disabled': is_operation_running }"
        >
            <label
                class="tlp-label"
                for="workflow-advanced-configuration"
                v-on:click.prevent="showModal()"
            >
                {{ $gettext("Use advanced configuration") }}
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
                    data-test="switch-mode"
                    v-bind:checked="is_workflow_advanced"
                    v-bind:disabled="is_operation_running"
                />
                <label
                    data-test="switch-button-mode"
                    class="tlp-switch-button"
                    for="workflow-advanced-configuration"
                    v-on:click.prevent="showModal()"
                    aria-hidden=""
                ></label>
            </div>
        </div>
        <workflow-mode-switch-modal
            v-bind:confirm="confirm"
            v-bind:is_operation_running="is_workflow_mode_change_running"
        >
            <template slot="modal-body">
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
            </template>
            <template slot="switch-button-label">
                <span v-if="is_workflow_advanced" key="switch_to_simple">
                    {{ $gettext("Switch to simple configuration") }}
                </span>
                <span v-else key="switch_to_advanced">
                    {{ $gettext("Switch to advanced configuration") }}
                </span>
            </template>
        </workflow-mode-switch-modal>
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import { createModal } from "@tuleap/tlp-modal";
import WorkflowModeSwitchModal from "./WorkflowModeSwitchModal.vue";

export default {
    name: "WorkflowModeSwitch",
    components: { WorkflowModeSwitchModal },
    data() {
        return {
            modal: null,
        };
    },
    computed: {
        ...mapState(["is_operation_running", "is_workflow_mode_change_running"]),
        ...mapGetters(["is_workflow_advanced"]),
        advanced_configuration_tooltip() {
            return this.$gettext(
                "Use advanced configuration if you want to configure each transition independently.",
            );
        },
    },
    mounted() {
        this.modal = createModal(this.$refs.modal);
    },
    methods: {
        showModal() {
            this.modal.show();
        },
        async confirm() {
            await this.$store.dispatch("changeWorkflowMode", !this.is_workflow_advanced);
            this.modal.hide();
        },
    },
};
</script>
