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
                <comparison-header
                    v-bind:from_baseline_id="from_baseline_id"
                    v-bind:to_baseline_id="to_baseline_id"
                />

                <statistics/>

                <comparison-content
                    v-bind:from_baseline_id="from_baseline_id"
                    v-bind:to_baseline_id="to_baseline_id"
                />
            </div>
        </main>
    </div>
</template>

<script>
import Statistics from "./Statistics.vue";
import ComparisonContent from "./content/ComparisonContent.vue";
import ComparisonHeader from "./ComparisonHeader.vue";

export default {
    name: "ComparisonPage",
    components: { ComparisonHeader, ComparisonContent, Statistics },
    props: {
        from_baseline_id: { required: true, type: Number },
        to_baseline_id: { required: true, type: Number }
    }
};
</script>
