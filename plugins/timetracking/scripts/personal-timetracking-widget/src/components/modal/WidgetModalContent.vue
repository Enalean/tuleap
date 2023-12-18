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
    <div class="tlp-modal-body timetracking-details-modal-content">
        <div class="tlp-pane-section timetracking-details-modal-artifact-title">
            <widget-link-to-artifact v-bind:artifact="artifact" />
        </div>
        <div class="timetracking-details-modal-artifact-details">
            <widget-modal-artifact-info v-bind:project="project" v-bind:artifact="artifact" />
            <div class="timetracking-details-modal-artefact-link-top-bottom-spacer">
                <button
                    class="tlp-button-primary"
                    data-test="button-set-add-mode"
                    v-on:click="personal_store.setAddMode(!personal_store.is_add_mode)"
                >
                    <i class="fa fa-plus tlp-button-icon"></i>
                    {{ $gettext("Add") }}
                </button>
            </div>
            <div
                v-if="personal_store.rest_feedback.type"
                v-bind:class="feedback_class"
                data-test="feedback"
            >
                {{ feedback_message }}
            </div>
            <widget-modal-table v-bind:artifact="artifact" />
        </div>
    </div>
</template>
<script>
import {
    REST_FEEDBACK_ADD,
    REST_FEEDBACK_EDIT,
    REST_FEEDBACK_DELETE,
    ERROR_OCCURRED,
} from "@tuleap/plugin-timetracking-constants";
import WidgetModalArtifactInfo from "./WidgetModalArtifactInfo.vue";
import WidgetModalTable from "./WidgetModalTable.vue";
import WidgetLinkToArtifact from "../WidgetLinkToArtifact.vue";
import { usePersonalTimetrackingWidgetStore } from "../../store";
import { mapState, mapActions } from "pinia";
export default {
    name: "WidgetModalContent",
    components: { WidgetLinkToArtifact, WidgetModalTable, WidgetModalArtifactInfo },
    props: {
        artifact: Object,
        project: Object,
    },
    setup() {
        const personal_store = usePersonalTimetrackingWidgetStore();

        return { personal_store };
    },
    computed: {
        ...mapState(usePersonalTimetrackingWidgetStore, ["is_add_mode"]),
        ...mapActions(usePersonalTimetrackingWidgetStore, ["setAddMode"]),

        feedback_class() {
            return "tlp-alert-" + this.personal_store.rest_feedback.type;
        },
        feedback_message() {
            switch (this.personal_store.rest_feedback.message) {
                case REST_FEEDBACK_ADD:
                    return this.$gettext("Time successfully added");
                case REST_FEEDBACK_EDIT:
                    return this.$gettext("Time successfully updated");
                case REST_FEEDBACK_DELETE:
                    return this.$gettext("Time successfully deleted");
                case ERROR_OCCURRED:
                    return this.$gettext("An error occurred");
                default:
                    return this.personal_store.rest_feedback.message;
            }
        },
    },
};
</script>
