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
  -
  -->

<template>
    <div class="statistics">
        <statistic-item
            v-bind:label="added_artifact_label"
            class="comparison-statistic-added-artifacts"
        >
            {{ added_artifacts_count }}
        </statistic-item>

        <statistic-item
            v-bind:label="removed_artifact_label"
            class="comparison-statistic-deleted-artifacts"
        >
            {{ removed_artifacts_count }}
        </statistic-item>

        <statistic-item
            v-bind:label="modified_artifact_label"
            class="comparison-statistic-modified-artifacts"
        >
            {{ modified_artifacts_count }}
        </statistic-item>
    </div>
</template>

<script>
import StatisticItem from "../common/StatisticItem.vue";
import { mapState } from "vuex";

export default {
    name: "ComparisonStatistics",

    components: { StatisticItem },

    filters: {
        with_sign(value) {
            if (value >= 0) {
                return `+ ${value}`;
            }
            return `- ${Math.abs(value)}`;
        },
    },

    computed: {
        ...mapState("comparison", [
            "added_artifacts_count",
            "removed_artifacts_count",
            "modified_artifacts_count",
        ]),
        added_artifact_label() {
            if (this.added_artifacts_count > 1) {
                return this.$gettext("Artifacts added");
            }
            return this.$gettext("Artifact added");
        },
        removed_artifact_label() {
            if (this.removed_artifacts_count > 1) {
                return this.$gettext("Artifacts deleted");
            }
            return this.$gettext("Artifact deleted");
        },
        modified_artifact_label() {
            if (this.modified_artifacts_count > 1) {
                return this.$gettext("Artifacts modified");
            }
            return this.$gettext("Artifact modified");
        },
    },
};
</script>
