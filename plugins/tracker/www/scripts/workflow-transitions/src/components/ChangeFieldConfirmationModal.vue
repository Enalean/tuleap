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
    <div
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="modal-confirm-change-field-label"
    >
        <div class="tlp-modal-header">
            <h1
                class="tlp-modal-title"
                id="modal-confirm-change-field-label"
                v-translate
            >Change or remove field</h1>
            <div class="tlp-modal-close" data-dismiss="modal" aria-label="Close">&times;</div>
        </div>
        <div class="tlp-modal-body">
            <p>
                <span v-translate>Are you sure to change transitions based field?</span>
                <br>
                <span v-translate>This action will delete all transition rules! It is irreversible.</span>
            </p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
                v-translate
            >Cancel</button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                v-on:click="confirm()"
                v-bind:disabled="is_operation_running"
                data-dismiss="modal"
            >
                <i v-if="is_operation_running" class="tlp-button-icon fa fa-spinner fa-spin"></i>
                <span v-translate>Confirm</span>
            </button>
        </div>
    </div>
</template>

<script>
import { mapState } from "vuex";

export default {
    name: "ChangeFieldConfirmationModal",

    computed: {
        ...mapState(["current_tracker", "is_operation_running"])
    },

    methods: {
        confirm() {
            this.$store
                .dispatch("resetWorkflowTransitionsField", this.current_tracker.id)
                .then(() => {
                    const feedback_box = document.getElementById("feedback");
                    const feedback_section_content = document.createElement("section");
                    feedback_section_content.classList.add("tlp-alert-info");
                    feedback_section_content.insertAdjacentText(
                        "afterbegin",
                        this.$gettext("Transitions rules was deleted. Workflow is reset.")
                    );
                    feedback_box.appendChild(feedback_section_content);
                });
        }
    }
};
</script>
