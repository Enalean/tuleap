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
  -
  -->

<template>
    <div>
        <div
            v-if="is_loading_failed"
            class="tlp-alert-danger tlp-framed-vertically"
            data-test-type="error-message"
        >
            <translate>Cannot fetch baseline</translate>
        </div>

        <content-page-skeleton v-else-if="is_loading"/>

        <template v-else>
            <baseline-label v-bind:baseline="baseline"/>

            <statistics/>

            <div class="tlp-framed-vertically">
                <section class="tlp-pane">
                    <div class="tlp-pane-container">
                        <section class="tlp-pane-section baseline-content">
                            <content-body/>
                        </section>
                    </div>
                </section>
            </div>
        </template>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";
import Statistics from "./Statistics.vue";
import ContentBody from "./ContentBody.vue";
import BaselineLabel from "../common/BaselineLabel.vue";
import ContentPageSkeleton from "./ContentPageSkeleton.vue";
import { mapGetters } from "vuex";

export default {
    name: "ContentPage",
    components: {
        BaselineLabel,
        ContentBody,
        Statistics,
        ContentPageSkeleton
    },

    props: {
        baseline_id: { required: true, type: Number }
    },

    data() {
        return {
            is_loading: true,
            is_loading_failed: false
        };
    },

    computed: {
        ...mapGetters(["findBaselineById"]),
        baseline() {
            return this.findBaselineById(this.baseline_id);
        }
    },

    created() {
        const title = sprintf(this.$gettext("Baseline #%u"), this.baseline_id);
        this.$emit("title", title);
    },

    mounted() {
        this.loadBaseline();
    },

    methods: {
        async loadBaseline() {
            this.is_loading = true;
            this.is_loading_failed = false;
            try {
                await this.$store.dispatch("current_baseline/load", this.baseline_id);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
