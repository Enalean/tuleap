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
    <form
        v-on:submit.prevent="createWorkflowTransitions()"
        data-test="tracker-workflow-first-configuration"
    >
        <section class="tlp-pane-section">
            <p>
                {{
                    $gettext(
                        "In order to configure transitions rules on this tracker, your first need to choose a list field. Once chosen, you will be able to configure transition using the configuration matrix.",
                    )
                }}
            </p>

            <div class="tlp-form-element">
                <label for="workflow-field" class="tlp-label">
                    <span> {{ $gettext("Field") }}</span>
                    <span
                        class="tlp-tooltip tlp-tooltip-top"
                        v-bind:data-tlp-tooltip="$gettext(`Transitions based field`)"
                    >
                        <i class="fa fa-question-circle"></i>
                    </span>
                    <i class="fa fa-asterisk"></i>
                </label>
                <select
                    id="workflow-field"
                    class="tlp-select tlp-select-adjusted"
                    name="field"
                    v-model="selected_field"
                    required
                    v-bind:disabled="is_operation_running"
                    data-test="list-fields"
                >
                    <option value disabled></option>
                    <option
                        v-for="field in selectbox_fields"
                        v-bind:key="field.id"
                        v-bind:value="field"
                    >
                        {{ field.label }}
                    </option>
                </select>
            </div>
        </section>
        <section class="tlp-pane-section tlp-pane-section-submit">
            <button
                class="tlp-button-primary"
                type="submit"
                v-bind:disabled="is_operation_running"
                data-test="create-workflow"
            >
                <i
                    class="tlp-button-icon fa"
                    v-bind:class="{
                        'fa-circle-o-notch fa-spin': is_operation_running,
                        'fa-long-arrow-right': !is_operation_running,
                    }"
                ></i>
                <span> {{ $gettext("Save and start configuration") }}</span>
            </button>
        </section>
    </form>
</template>

<script>
import { mapState, mapGetters } from "vuex";

export default {
    name: "FirstConfigurationSections",

    data() {
        return {
            selected_field: null,
        };
    },

    computed: {
        ...mapState(["is_operation_running"]),
        ...mapGetters(["selectbox_fields"]),
    },

    methods: {
        createWorkflowTransitions() {
            this.$store.dispatch("createWorkflowTransitions", this.selected_field.id).then(() => {
                const feedback_box = document.getElementById("feedback");
                while (feedback_box.firstChild) {
                    feedback_box.removeChild(feedback_box.firstChild);
                }
            });
        },
    },
};
</script>
