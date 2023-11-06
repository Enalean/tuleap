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
    <section class="tlp-pane-section tracker-workflow-transition-configuration-header">
        <workflow-field-change />
        <workflow-mode-switch />
        <div
            class="tlp-form-element tracker-workflow-transition-configuration-form-item"
            v-bind:class="{ 'tlp-form-element-disabled': is_operation_running }"
        >
            <label class="tlp-label" for="workflow-transitions-enabled">
                {{ $gettext("Enable transition rules") }}
            </label>
            <div class="tlp-switch">
                <input
                    type="checkbox"
                    id="workflow-transitions-enabled"
                    class="tlp-switch-checkbox"
                    v-model="transition_rules_enforcement"
                    v-bind:disabled="is_operation_running"
                />
                <label
                    for="workflow-transitions-enabled"
                    class="tlp-switch-button"
                    v-bind:class="{ loading: is_rules_enforcement_running }"
                    aria-hidden=""
                ></label>
            </div>
        </div>
    </section>
</template>
<script>
import { mapGetters, mapState } from "vuex";
import WorkflowModeSwitch from "./WorkflowModeSwitch.vue";
import WorkflowFieldChange from "./WorkflowFieldChange.vue";

export default {
    name: "HeaderSection",
    components: {
        WorkflowFieldChange,
        WorkflowModeSwitch,
    },
    computed: {
        ...mapState(["is_operation_running", "is_rules_enforcement_running"]),
        ...mapGetters(["are_transition_rules_enforced"]),
        transition_rules_enforcement: {
            get() {
                return this.are_transition_rules_enforced;
            },
            set() {
                this.$store.dispatch(
                    "updateTransitionRulesEnforcement",
                    !this.are_transition_rules_enforced,
                );
            },
        },
    },
};
</script>
