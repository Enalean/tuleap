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
    <tr>
        <td class="tlp-table-cell-numeric">
            <a href="#" v-on:click.prevent="showComparison(comparison)">
                {{ comparison.id }}
            </a>
        </td>
        <td class="comparisons-table-column-name">{{ comparison.name }}</td>
        <td class="comparisons-table-column-milestone">
            <artifact-link class="baselines-table-column-milestone" v-bind:artifact="milestone">
                <artifact-badge
                    v-bind:artifact="milestone"
                    v-bind:tracker="milestone_tracker"
                />
                {{ milestone.title }}
            </artifact-link>
        </td>
        <td class="comparisons-table-column-author">
            <user-badge v-bind:user="author"/>
        </td>
        <td class="comparisons-table-column-snapshot-date">
            <humanized-date v-bind:date="comparison.creation_date" v-bind:start_with_capital="true"/>
        </td>
        <td class="tlp-table-cell-actions">
            <action-button
                icon="eye"
                class="tlp-table-cell-actions-button"
                v-on:click="showComparison()"
                data-test-action="consult"
            >
                <span v-translate>Consult</span>
            </action-button>
        </td>
    </tr>
</template>

<script>
import HumanizedDate from "../common/HumanizedDate.vue";
import UserBadge from "../common/UserBadge.vue";
import ArtifactLink from "../common/ArtifactLink.vue";
import ArtifactBadge from "../common/ArtifactBadge.vue";
import ActionButton from "../common/ActionButton.vue";
import { mapGetters } from "vuex";

export default {
    name: "Comparison",
    components: {
        ArtifactLink,
        ArtifactBadge,
        HumanizedDate,
        UserBadge,
        ActionButton
    },

    props: {
        comparison: { required: true, type: Object }
    },

    computed: {
        ...mapGetters(["findBaselineById", "findArtifactById", "findTrackerById", "findUserById"]),
        base_baseline() {
            return this.findBaselineById(this.comparison.base_baseline_id);
        },
        milestone() {
            return this.findArtifactById(this.base_baseline.artifact_id);
        },
        milestone_tracker() {
            return this.findTrackerById(this.milestone.tracker.id);
        },
        author() {
            return this.findUserById(this.comparison.author_id);
        }
    },

    methods: {
        showComparison() {
            this.$router.push({
                name: "ComparisonPage",
                params: { comparison_id: this.comparison.id }
            });
        }
    }
};
</script>
