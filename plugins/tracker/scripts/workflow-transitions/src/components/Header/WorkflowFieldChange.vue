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
                <span>{{ $gettext("Field") }}</span>
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
                    <span>{{ $gettext("Change or remove") }}</span>
                </button>
            </div>
        </div>
        <change-field-confirmation-modal
            v-if="is_shown"
            data-test="change-field-confirmation-modal"
            v-on:close-modal="closeModal"
        />
    </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import ChangeFieldConfirmationModal from "./ChangeFieldConfirmationModal.vue";

export default {
    name: "WorkflowFieldChange",
    components: { ChangeFieldConfirmationModal },
    data() {
        return {
            is_shown: false,
        };
    },
    computed: {
        ...mapState(["is_operation_running"]),
        ...mapGetters(["workflow_field_label"]),
    },
    methods: {
        showModal() {
            this.is_shown = true;
        },
        closeModal() {
            this.is_shown = false;
        },
    },
};
</script>
