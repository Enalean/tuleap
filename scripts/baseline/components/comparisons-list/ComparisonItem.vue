<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <tr>
        <td class="tlp-table-cell-numeric">
            <a href="#" v-on:click.prevent="showComparison()">
                {{ comparison.id }}
            </a>
        </td>
        <td class="comparisons-table-column">
            <artifact-link class="baselines-table-column-milestone" v-bind:artifact="milestone">
                <artifact-badge v-bind:artifact="milestone" v-bind:tracker="milestone_tracker" />
                {{ milestone.title }}
            </artifact-link>
        </td>
        <td class="comparisons-table-column">
            {{ base_baseline.name }}
        </td>
        <td class="comparisons-table-column">
            {{ compared_to_baseline.name }}
        </td>
        <td class="tlp-table-cell-actions">
            <consult-comparison-button
                class="tlp-table-cell-actions-button"
                v-bind:comparison="comparison"
            />
            <delete-comparison-button
                class="tlp-table-cell-actions-button"
                v-bind:comparison="comparison"
                v-bind:base_baseline="base_baseline"
                v-bind:compared_to_baseline="compared_to_baseline"
            />
        </td>
    </tr>
</template>

<script>
import ArtifactLink from "../common/ArtifactLink.vue";
import ArtifactBadge from "../common/ArtifactBadge.vue";
import { mapGetters } from "vuex";
import DeleteComparisonButton from "./DeleteComparisonButton.vue";
import ConsultComparisonButton from "./ConsultComparisonButton.vue";

export default {
    name: "ComparisonItem",
    components: {
        ConsultComparisonButton,
        DeleteComparisonButton,
        ArtifactLink,
        ArtifactBadge,
    },

    props: {
        comparison: { required: true, type: Object },
    },

    computed: {
        ...mapGetters(["findBaselineById", "findArtifactById", "findTrackerById", "findUserById"]),
        base_baseline() {
            return this.findBaselineById(this.comparison.base_baseline_id);
        },
        compared_to_baseline() {
            return this.findBaselineById(this.comparison.compared_to_baseline_id);
        },
        milestone() {
            return this.findArtifactById(this.base_baseline.artifact_id);
        },
        milestone_tracker() {
            return this.findTrackerById(this.milestone.tracker.id);
        },
    },

    methods: {
        showComparison() {
            this.$router.push({
                name: "ComparisonPage",
                params: { comparison_id: this.comparison.id },
            });
        },
    },
};
</script>
