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
    <div>
        <div v-if="are_transition_rules_enforced === true"
             key="enforcement_enabled"
             v-translate
             class="tlp-alert-success"
        >
            Transition rules are currently applied.
        </div>
        <div v-if="are_transition_rules_enforced === false"
             key="enforcement_disabled"
             v-translate
             class="tlp-alert-warning"
        >
            Transition rules don't apply yet.
        </div>

        <div v-if="is_current_tracker_load_failed" v-translate class="tlp-alert-danger">
            Tracker cannot be loaded
        </div>
        <template v-else>
            <div v-if="is_operation_failed" class="tlp-alert-danger">
                {{ translated_operation_failure_message }}
            </div>
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title" v-translate>Transitions rules configuration</h1>
                    </div>
                    <template v-if="is_tracker_available">
                        <template v-if="is_base_field_configured">
                            <transitions-configuration-header-section/>
                            <transitions-matrix-section/>
                        </template>
                        <first-configuration-sections v-else/>
                    </template>
                    <section class="tlp-pane-section" v-else>
                        <div class="tracker-workflow-loader"></div>
                    </section>
                </div>
            </section>
        </template>
    </div>
</template>

<script>
import FirstConfigurationSections from "./FirstConfigurationSections.vue";
import TransitionsConfigurationHeaderSection from "./TransitionsConfigurationHeaderSection.vue";
import TransitionsMatrixSection from "./TransitionsMatrixSection.vue";
import { mapState, mapGetters } from "vuex";

export default {
    name: "BaseTrackerWorflowTransitions",
    components: {
        FirstConfigurationSections,
        TransitionsConfigurationHeaderSection,
        TransitionsMatrixSection
    },

    props: {
        trackerId: {
            type: Number,
            mandatory: true
        }
    },

    computed: {
        ...mapState([
            "is_current_tracker_loading",
            "current_tracker",
            "is_current_tracker_load_failed",
            "is_operation_failed",
            "operation_failure_message"
        ]),
        ...mapGetters(["are_transition_rules_enforced"]),
        is_tracker_available() {
            return !this.is_current_tracker_loading && this.current_tracker;
        },
        is_base_field_configured() {
            return (
                this.current_tracker &&
                this.current_tracker.workflow &&
                Boolean(this.current_tracker.workflow.field_id)
            );
        },
        translated_operation_failure_message() {
            return this.operation_failure_message || this.$gettext("An error occurred");
        }
    },

    mounted() {
        this.$store.dispatch("loadTracker", this.trackerId);
    }
};
</script>
