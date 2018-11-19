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
        <div v-if="is_current_tracker_load_failed" v-translate class="tlp-alert-danger">
            Tracker cannot be loaded
        </div>
        <section v-else class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title" v-translate>Transitions rules configuration</h1>
                </div>
                <template v-if="is_tracker_available">
                    <template v-if="is_base_field_configured">
                        <transitions-configuration-header-section/>
                        <transitions-matrix-section/>
                    </template>
                    <first-configuration-section v-else/>
                </template>
                <section class="tlp-pane-section" v-else>
                    <div class="tracker-workflow-loader"></div>
                </section>
            </div>
        </section>
    </div>
</template>

<script>
import FirstConfigurationSection from "./FirstConfigurationSection.vue";
import TransitionsConfigurationHeaderSection from "./TransitionsConfigurationHeaderSection.vue";
import TransitionsMatrixSection from "./TransitionsMatrixSection.vue";
import { mapState } from "vuex";

export default {
    name: "BaseTrackerWorflowTransitions",
    components: {
        FirstConfigurationSection,
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
            "is_current_tracker_load_failed"
        ]),
        is_tracker_available() {
            return !this.is_current_tracker_loading && this.current_tracker;
        },
        is_base_field_configured() {
            return (
                this.current_tracker &&
                this.current_tracker.workflow &&
                Boolean(this.current_tracker.workflow.field_id)
            );
        }
    },

    mounted() {
        this.$store.dispatch("loadTracker", this.trackerId);
    }
};
</script>
