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
        <nav class="breadcrumb">
            <div class="breadcrumb-item">
                <button type="button" class="breadcrumb-link baseline-breadcrumb-link" title="Baselines">
                    Baselines
                </button>
            </div>
            <div class="breadcrumb-item">
                <button
                    v-on:click="goTo(`baselines/${baseline_id}`)"
                    type="button"
                    class="breadcrumb-link baseline-breadcrumb-link "
                    title="Baseline"
                >
                    Baseline
                </button>
            </div>
        </nav>

        <main class="tlp-framed-vertically">
            <div class="tlp-framed-horizontally">
                <div
                    v-if="is_loading_failed"
                    class="tlp-alert-danger tlp-framed-vertically"
                    data-test-type="error-message"
                >
                    <translate>Cannot fetch baseline</translate>
                </div>
                <span v-else-if="is_loading" class="tlp-skeleton-text" data-test-type="baseline-header-skeleton"></span>

                <template v-else>
                    <h2 data-test-type="baseline-header">
                        Baseline #{{ baseline_id }} - {{ baseline.name }} <small>{{ baseline.snapshot_date }}</small>
                        <span class="baseline-header-author">
                            <small><translate>Created by</translate> {{ baseline.author.username }}</small>
                        </span>
                    </h2>

                    <baseline-statistics/>
                </template>
            </div>
        </main>
    </div>
</template>

<script>
import { getBaseline } from "../api/rest-querier";
import { presentBaseline } from "../presenters/baseline";
import BaselineStatistics from "./BaselineStatistics.vue";

export default {
    name: "BaselinePage",
    components: { BaselineStatistics },
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
