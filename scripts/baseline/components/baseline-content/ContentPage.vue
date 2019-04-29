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
            v-if="is_baseline_loading_failed"
            class="tlp-alert-danger tlp-framed-vertically"
            data-test-type="error-message"
        >
            <translate>Cannot fetch baseline</translate>
        </div>

        <template v-else>
            <baseline-label-skeleton v-if="!is_baseline_available" data-test-type="baseline-header-skeleton"/>

            <baseline-label v-else v-bind:baseline="baseline"/>

            <statistics/>

            <div class="tlp-framed-vertically">
                <section class="tlp-pane">
                    <div class="tlp-pane-container">
                        <section class="tlp-pane-section">
                            <content-body-skeleton v-if="!is_baseline_available"/>
                            <content-body v-else v-bind:first_depth_artifacts="baseline.first_depth_artifacts"/>
                        </section>
                    </div>
                </section>
            </div>
        </template>
    </div>
</template>

<script>
import { sprintf } from "sprintf-js";
import { mapState } from "vuex";
import Statistics from "./Statistics.vue";
import ContentBody from "./ContentBody.vue";
import BaselineLabel from "../common/BaselineLabel.vue";
import BaselineLabelSkeleton from "../common/BaselineLabelSkeleton.vue";
import ContentBodySkeleton from "./ContentBodySkeleton.vue";

export default {
    name: "ContentPage",
    components: {
        BaselineLabelSkeleton,
        BaselineLabel,
        ContentBody,
        Statistics,
        ContentBodySkeleton
    },
    props: {
        baseline_id: { required: true, type: Number }
    },

    computed: {
        ...mapState("baseline", ["baseline", "is_baseline_loading_failed", "is_baseline_loading"]),

        is_baseline_available() {
            return !this.is_baseline_loading && this.baseline;
        }
    },

    created() {
        const title = sprintf(this.$gettext("Baseline #%u"), this.baseline_id);
        this.$emit("title", title);
    },

    mounted() {
        this.$store.dispatch("baseline/load", this.baseline_id);
    }
};
</script>
