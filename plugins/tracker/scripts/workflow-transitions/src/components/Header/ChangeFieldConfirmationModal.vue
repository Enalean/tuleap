<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
        ref="modal"
        class="tlp-modal tlp-modal-danger"
        role="dialog"
        aria-labelledby="modal-confirm-change-field-label"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title" id="modal-confirm-change-field-label">
                {{ $gettext("Change or remove field") }}
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
            <p>
                {{
                    $gettext(
                        "Are you sure you want to change the field used to define the transitions?",
                    )
                }}
            </p>
            <p>
                {{ $gettext("This action will delete all transition rules! It is irreversible.") }}
            </p>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                class="tlp-button-danger tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-danger tlp-modal-action"
                v-on:click="confirm()"
                v-bind:disabled="is_operation_running"
                data-test="confirm-button"
            >
                <i
                    v-if="is_operation_running"
                    class="tlp-button-icon fas fa-circle-notch fa-spin"
                    data-test="confirm-button-spinner"
                ></i>
                {{ $gettext("Confirm") }}
            </button>
        </div>
    </div>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import { createModal } from "@tuleap/tlp-modal";

export default {
    name: "ChangeFieldConfirmationModal",
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
        this.modal.addEventListener("tlp-modal-hidden", this.reset);
        this.modal.show();
    },
    methods: {
        async confirm() {
            await this.$store.dispatch("resetWorkflowTransitions", this.current_tracker_id);
            const feedback_box = document.getElementById("feedback");
            const feedback_section_content = document.createElement("section");
            feedback_section_content.classList.add("tlp-alert-info");
            feedback_section_content.insertAdjacentText(
                "afterbegin",
                this.$gettext("Transitions rules were deleted. Workflow is reset."),
            );
            feedback_box.appendChild(feedback_section_content);
            this.modal.hide();
        },
        reset() {
            this.$emit("close-modal");
        },
    },
};
</script>
