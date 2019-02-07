<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
        <main class="tlp-framed-vertically">
            <h1 class="tlp-framed-horizontally">Simplified Baseline</h1>
            <div class="tlp-framed-horizontally">
                <section class="tlp-pane">
                    <div class="tlp-pane-container">
                        <div
                            data-test-type="error-message"
                            class="tlp-alert-danger"
                            v-if="loading_error_message"
                        >
                            {{ this.loading_error_message }}
                        </div>
                        <skeleton-baseline
                            v-else-if="is_loading"
                        />
                        <simplified-baseline
                            v-else
                            v-bind:baseline="baseline"
                        />
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<script>
import { getBaseline } from "../api/rest-querier";
import SkeletonBaseline from "./SkeletonBaseline.vue";
import SimplifiedBaseline from "./SimplifiedBaseline.vue";

export default {
    name: "App",

    components: {
        SkeletonBaseline,
        SimplifiedBaseline
    },

    props: {
        artifact_id: { mandatory: true, type: Number },
        date: { mandatory: true, type: String }
    },

    data() {
        return {
            baseline: null,
            loading_error_message: null,
            is_loading: true
        };
    },

    mounted() {
        this.fetchBaseline(this.artifact_id, this.date);
    },

    methods: {
        async fetchBaseline(artifact_id, date) {
            this.is_loading = true;
            this.loading_error_message = null;

            try {
                this.baseline = await getBaseline(artifact_id, date);
            } catch (e) {
                this.loading_error_message = this.$gettext("Cannot fetch data");
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
