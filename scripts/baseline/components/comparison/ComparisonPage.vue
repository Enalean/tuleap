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
        <nav class="breadcrumb">
            <div class="breadcrumb-item">
                <router-link
                    v-bind:to="{ name: 'BaselinesPage' }"
                    tag="button"
                    class="breadcrumb-link baseline-breadcrumb-link"
                    title="Baselines"
                    v-translate
                >
                    Baselines
                </router-link>
            </div>
            <div class="breadcrumb-item">
                <router-link
                    v-bind:to="{ name: 'ComparisonPage', params: { from_baseline_id, to_baseline_id } }"
                    tag="button"
                    class="breadcrumb-link baseline-breadcrumb-link"
                    title="Baseline"
                >
                    <translate>Baselines comparison:</translate> #{{ from_baseline_id }} / #{{ to_baseline_id }}
                </router-link>
            </div>
        </nav>

        <main class="tlp-framed-vertically">
            <div class="tlp-framed-horizontally">
                <div
                    v-if="is_loading_failed"
                    class="tlp-alert-danger tlp-framed-vertically"
                    data-test-type="error-message"
                    v-translate
                >
                    Cannot fetch baselines
                </div>
                <template v-else-if="is_loading">
                    <baseline-label-skeleton key="from"/>
                    <baseline-label-skeleton key="to"/>
                </template>
                <template v-else>
                    <baseline-label v-bind:baseline="from_baseline" key="from"/>
                    <baseline-label v-bind:baseline="to_baseline" key="to"/>
                </template>

                <comparison-statistics/>

                <div class="tlp-framed-vertically">
                    <section class="tlp-pane">
                        <div class="tlp-pane-container">
                            <section class="tlp-pane-section comparison-content">
                                <!-- comparison content -->
                            </section>
                        </div>
                    </section>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
import { getBaseline } from "../../api/rest-querier";
import { presentBaseline } from "../../presenters/baseline";
import BaselineLabel from "../common/BaselineLabel.vue";
import BaselineLabelSkeleton from "../common/BaselineLabelSkeleton.vue";
import ComparisonStatistics from "./ComparisonStatistics.vue";

export default {
    name: "ComparisonPage",
    components: { BaselineLabel, BaselineLabelSkeleton, ComparisonStatistics },
    props: {
        from_baseline_id: { required: true, type: Number },
        to_baseline_id: { required: true, type: Number }
    },

    data() {
        return {
            from_baseline: null,
            to_baseline: null,
            is_loading: true,
            is_loading_failed: false
        };
    },

    mounted() {
        this.fetchBaselines();
    },

    methods: {
        async fetchBaselines() {
            this.is_loading = true;
            this.is_loading_failed = false;

            try {
                const from_baseline = this.getPresentedBaseline(this.from_baseline_id);
                const to_baseline = this.getPresentedBaseline(this.to_baseline_id);
                this.to_baseline = await to_baseline;
                this.from_baseline = await from_baseline;
            } catch (e) {
                this.is_loading_failed = true;
            } finally {
                this.is_loading = false;
            }
        },

        async getPresentedBaseline(baseline_id) {
            const baseline = await getBaseline(baseline_id);
            return presentBaseline(baseline);
        }
    }
};
</script>
