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
            class="tlp-property tracker-workflow-transition-configuration-form-item"
            v-bind:class="{ 'tracker-workflow-property-disabled': is_operation_running }"
        >
            <label class="tlp-label">
                <span v-translate>Field</span>
                <span
                    class="tlp-tooltip tlp-tooltip-top"
                    v-bind:data-tlp-tooltip="$gettext('Transitions based field')"
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
                    v-bind:disabled="is_operation_running"
                    data-test="change-or-remove-button"
                >
                    <i class="fas fa-sync tlp-button-icon"></i>
                    <span v-translate>Change or remove</span>
                </button>
            </div>
        </div>
        <change-field-confirmation-modal
            v-bind:confirm="confirm"
            v-bind:is_operation_running="is_operation_running"
        />
    </div>
</template>
<script>
import { createModal } from "@tuleap/tlp-modal";
import { mapState, mapGetters } from "vuex";
import ChangeFieldConfirmationModal from "./ChangeFieldConfirmationModal.vue";

export default {
    name: "WorkflowFieldChange",
    components: { ChangeFieldConfirmationModal },
    data() {
        return {
            modal: null,
        };
    },
    computed: {
        ...mapState(["is_operation_running"]),
        ...mapGetters(["workflow_field_label", "current_tracker_id"]),
    },
    mounted() {
        this.modal = createModal(this.$refs.modal);
    },
    methods: {
        showModal() {
            this.modal.show();
        },
        async confirm() {
            await this.$store.dispatch("resetWorkflowTransitions", this.current_tracker_id);
            const feedback_box = document.getElementById("feedback");
            const feedback_section_content = document.createElement("section");
            feedback_section_content.classList.add("tlp-alert-info");
            feedback_section_content.insertAdjacentText(
                "afterbegin",
                this.$gettext("Transitions rules were deleted. Workflow is reset.")
            );
            feedback_box.appendChild(feedback_section_content);
            this.modal.hide();
        },
    },
};
</script>
