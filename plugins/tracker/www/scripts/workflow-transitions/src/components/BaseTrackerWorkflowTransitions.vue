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
        <div v-if="tracker_loading_failed" v-translate class="tlp-alert-danger">
            Tracker cannot be loaded
        </div>
        <section v-else class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title" v-translate>Transitions rules configuration</h1>
                </div>
                <section v-if="is_tracker_available" class="tlp-pane-section">
                    <div class="tracker-workflow-loader"></div>
                </section>
                <section v-else class="tlp-pane-section empty-page-text">
                    <span v-translate>Page under construction</span>
                </section>
            </div>
        </section>
    </div>
</template>

<script>
import { getTracker } from "../api/rest-querier.js";

export default {
    name: "BaseTrackerWorflowTransitions",

    props: {
        trackerId: {
            type: Number,
            mandatory: true
        }
    },

    data() {
        return {
            is_tracker_loading: false,
            tracker: null,
            tracker_loading_failed: false
        };
    },

    computed: {
        is_tracker_available() {
            return this.is_tracker_loading || this.tracker === null;
        }
    },

    mounted() {
        this.loadTracker();
    },

    methods: {
        async loadTracker() {
            try {
                this.is_tracker_loading = true;
                this.tracker = await getTracker(this.trackerId);
            } catch (e) {
                this.tracker_loading_failed = true;
            } finally {
                this.is_tracker_loading = false;
            }
        }
    }
};
</script>
