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
    <div class="statistics">
        <statistic
            v-bind:value="1"
            v-bind:label="added_artifact_statistic_label"
            class="comparison-statistic-added-artifacts"
        />
        <statistic
            v-bind:value="3"
            v-bind:label="deleted_artifact_statistic_label"
            class="comparison-statistic-deleted-artifacts"
        />
        <statistic
            v-bind:value="25"
            v-bind:label="modified_artifact_statistic_label"
            class="comparison-statistic-modified-artifacts"
        />
        <statistic
            v-bind:value="12"
            v-bind:label="initial_effort_statistic_label"
            class="comparison-statistic-initial-effort"
        />
    </div>
</template>

<script>
import Statistic from "../common/Statistic.vue";
export default {
    name: "ComparisonStatistics",

    components: { Statistic },

    computed: {
        added_artifact_statistic_label() {
            return this.$gettext("Artifact added");
        },
        deleted_artifact_statistic_label() {
            return this.$gettext("Artifacts deleted");
        },
        modified_artifact_statistic_label() {
            return this.$gettext("Artifacts modified");
        },
        initial_effort_statistic_label() {
            return this.$gettext("Initial effort");
        }
    }
};
</script>
