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
    <section class="tlp-pane-section">
        <div class="tracker-workflow-transition-configuration-header">
            <div class="tlp-property tracker-workflow-transition-configuration-form-item">
                <label class="tlp-label">
                    <span v-translate>Field</span>
                    <span
                        class="tlp-tooltip tlp-tooltip-top"
                        v-bind:data-tlp-tooltip="field_tooltip"
                    >
                        <i class="fa fa-question-circle"></i>
                    </span>
                </label>
                <div>
                    <span>{{ workflow_field_label }}</span>
                    <button
                        class="tlp-button-danger tlp-button-outline tlp-button-small tracker-workflow-transition-configuration-form-button"
                        data-target="modal-confirm-change-field"
                        v-on:click="showModal()"
                    >
                        <i class="fa fa-refresh tlp-button-icon"></i>
                        <span v-translate>Change or remove</span>
                    </button>

                    <change-field-confirmation-modal ref="modal"/>
                </div>
            </div>
            <div class="tlp-form-element tracker-workflow-transition-configuration-form-item">
                <label class="tlp-label" for="workflow-advanced-configuration">
                    <span v-translate>Use advanced configuration</span>
                    <span
                        class="tlp-tooltip tlp-tooltip-top"
                        v-bind:data-tlp-tooltip="advanced_configuration_tooltip"
                    >
                        <i class="fa fa-question-circle"></i>
                    </span>
                </label>
                <div class="tlp-switch">
                    <input
                        type="checkbox"
                        id="workflow-advanced-configuration"
                        class="tlp-switch-checkbox"
                        checked
                        disabled
                    >
                    <label
                        for="workflow-advanced-configuration"
                        class="tlp-switch-button"
                        aria-hidden=""
                    ></label>
                </div>
            </div>
            <div class="tlp-form-element tracker-workflow-transition-configuration-form-item">
                <label class="tlp-label" for="workflow-transitions-enabled" v-translate>Enable transition rules</label>
                <div class="tlp-switch">
                    <input
                        type="checkbox"
                        id="workflow-transitions-enabled"
                        class="tlp-switch-checkbox"
                        v-model="are_transition_rules_enforced"
                        disabled
                    >
                    <label
                        for="workflow-transitions-enabled"
                        class="tlp-switch-button"
                        aria-hidden=""
                    ></label>
                </div>
            </div>
        </div>
    </section>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import { modal as createModal } from "tlp";
import ChangeFieldConfirmationModal from "./ChangeFieldConfirmationModal.vue";

export default {
    name: "TransitionsConfigurationHeaderSection",

    components: {
        ChangeFieldConfirmationModal
    },

    data() {
        return {
            modal: null
        };
    },

    computed: {
        ...mapState(["current_tracker"]),
        ...mapGetters(["workflow_field_label", "are_transition_rules_enforced"]),
        advanced_configuration_tooltip() {
            return this.$gettext(
                "Use advanced configuration if you want to configure each transition independently."
            );
        },
        field_tooltip() {
            return this.$gettext("Transitions based field");
        }
    },

    mounted() {
        const modal = this.$refs.modal.$el;
        this.modal = createModal(modal);
    },

    methods: {
        showModal() {
            this.modal.toggle();
        }
    }
};
</script>
