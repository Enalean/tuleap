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
            <template v-slot:default>
                {{ added_artifacts_count }}
            </template>
        </statistic-item>

        <statistic-item
            v-bind:label="removed_artifact_label"
            class="comparison-statistic-deleted-artifacts"
        >
            <template v-slot:default>
                {{ removed_artifacts_count }}
            </template>
        </statistic-item>

        <statistic-item
            v-bind:label="modified_artifact_label"
            class="comparison-statistic-modified-artifacts"
        >
            <template v-slot:default>
                {{ modified_artifacts_count }}
            </template>
        </statistic-item>
    </div>
</template>

<script>
import StatisticItem from "../common/StatisticItem.vue";
import { mapState } from "vuex";

export default {
    name: "ComparisonStatistics",

    components: { StatisticItem },

    computed: {
        ...mapState("comparison", [
            "added_artifacts_count",
            "removed_artifacts_count",
            "modified_artifacts_count",
        ]),
        added_artifact_label() {
            return this.$ngettext("Artifact added", "Artifacts added", this.added_artifacts_count);
        },
        removed_artifact_label() {
            return this.$ngettext(
                "Artifact deleted",
                "Artifacts deleted",
                this.removed_artifacts_count,
            );
        },
        modified_artifact_label() {
            return this.$ngettext(
                "Artifact modified",
                "Artifacts modified",
                this.modified_artifacts_count,
            );
        },
    },
};
</script>
