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
        <workflow-mode-switch-modal v-if="show_modal" v-on:close-modal="hide" />
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import WorkflowModeSwitchModal from "./WorkflowModeSwitchModal.vue";

export default {
    name: "WorkflowModeSwitch",
    components: { WorkflowModeSwitchModal },
    data() {
        return {
            show_modal: false,
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
    methods: {
        showModal() {
            this.show_modal = true;
        },
        hide() {
            this.show_modal = false;
        },
    },
};
</script>
