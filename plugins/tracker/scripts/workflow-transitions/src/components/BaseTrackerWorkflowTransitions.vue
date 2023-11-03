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
    <div>
        <transition-rules-enforcement-warning v-if="is_base_field_configured" />
        <div
            v-if="is_current_tracker_load_failed"
            class="tlp-alert-danger"
            data-test-type="tracker-load-error-message"
        >
            {{ $gettext("Tracker cannot be loaded") }}
        </div>
        <template v-else>
            <div v-if="is_operation_failed" class="tlp-alert-danger">
                {{ translated_operation_failure_message }}
            </div>
            <section class="tlp-pane">
                <div class="tlp-pane-container">
                    <div class="tlp-pane-header">
                        <h1 class="tlp-pane-title">
                            {{ $gettext("Transitions rules configuration") }}
                        </h1>
                    </div>
                    <template v-if="is_tracker_available">
                        <template v-if="is_base_field_configured">
                            <header-section />
                            <transitions-matrix-section />
                        </template>
                        <first-configuration-sections v-else-if="has_selectbox_fields" />
                        <first-configuration-impossible-warning v-else />
                    </template>
                    <section class="tlp-pane-section" v-else>
                        <div
                            class="tracker-workflow-loader"
                            data-test-type="tracker-load-spinner"
                        ></div>
                    </section>
                </div>
            </section>
            <transition-modal />
        </template>
    </div>
</template>

<script>
import { mapState, mapGetters } from "vuex";
import FirstConfigurationSections from "./FirstConfiguration/FirstConfigurationSections.vue";
import HeaderSection from "./Header/HeaderSection.vue";
import TransitionsMatrixSection from "./TransitionsMatrixSection.vue";
import TransitionModal from "./TransitionModal/TransitionModal.vue";
import TransitionRulesEnforcementWarning from "./TransitionRulesEnforcementWarning.vue";
import FirstConfigurationImpossibleWarning from "./FirstConfiguration/FirstConfigurationImpossibleWarning.vue";

export default {
    name: "BaseTrackerWorkflowTransitions",
    components: {
        TransitionRulesEnforcementWarning,
        FirstConfigurationImpossibleWarning,
        TransitionModal,
        FirstConfigurationSections,
        HeaderSection,
        TransitionsMatrixSection,
    },

    props: {
        trackerId: {
            type: Number,
            mandatory: true,
        },
        used_services_names: {
            type: Array,
            mandatory: true,
        },
        is_split_feature_flag_enabled: {
            type: Boolean,
            mandatory: true,
        },
    },

    computed: {
        ...mapState([
            "is_current_tracker_loading",
            "current_tracker",
            "is_current_tracker_load_failed",
            "is_operation_failed",
            "operation_failure_message",
        ]),
        ...mapGetters(["has_selectbox_fields"]),
        is_tracker_available() {
            return !this.is_current_tracker_loading && Boolean(this.current_tracker);
        },
        is_base_field_configured() {
            return (
                Boolean(this.current_tracker) &&
                Boolean(this.current_tracker.workflow) &&
                Boolean(this.current_tracker.workflow.field_id)
            );
        },
        translated_operation_failure_message() {
            return this.operation_failure_message || this.$gettext("An error occurred");
        },
    },

    mounted() {
        this.$store.dispatch("loadTracker", this.trackerId);
        this.$store.dispatch("transitionModal/setUsedServiceName", this.used_services_names);
        this.$store.dispatch(
            "transitionModal/setIsSplitFeatureFlagEnabled",
            this.is_split_feature_flag_enabled,
        );
    },
};
</script>
