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
    <main class="tlp-framed-vertically">
        <div class="tlp-framed-horizontally">
            <div
                v-if="is_loading_failed"
                class="tlp-alert-danger tlp-framed-vertically"
                data-test-type="error-message"
            >
                <translate>Cannot fetch baseline</translate>
            </div>

            <baseline-label-skeleton v-else-if="is_loading" data-test-type="baseline-header-skeleton"/>

            <baseline-label v-else v-bind:baseline="baseline"/>

            <statistics/>

            <div class="tlp-framed-vertically">
                <section class="tlp-pane">
                    <div class="tlp-pane-container">
                        <section class="tlp-pane-section">
                            <content-body v-bind:baseline_id="baseline_id"/>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </main>
</template>

<script>
import { sprintf } from "sprintf-js";
import { getBaseline } from "../../api/rest-querier";
import { presentBaseline } from "../../presenters/baseline";
import Statistics from "./Statistics.vue";
import ContentBody from "./ContentBody.vue";
import BaselineLabel from "../common/BaselineLabel.vue";
import BaselineLabelSkeleton from "../common/BaselineLabelSkeleton.vue";

export default {
    name: "BaselinePage",
    components: { BaselineLabelSkeleton, BaselineLabel, ContentBody, Statistics },
    props: {
        baseline_id: { required: true, type: Number }
    },

    data() {
        return {
            baseline: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    created() {
        const title = sprintf(this.$gettext("Baseline #%u"), this.baseline_id);
        this.$emit("title", title);
    },

    mounted() {
        this.fetchBaseline();
    },

    methods: {
        async fetchBaseline() {
            this.baseline = null;
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                const baseline = await getBaseline(this.baseline_id);
                this.baseline = await presentBaseline(baseline);
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        }
    }
};
</script>
