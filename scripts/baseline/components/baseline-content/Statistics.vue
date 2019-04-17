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
        <statistic v-bind:value="5" v-bind:label="epics_label" class="baseline-statistic-epics"/>
        <statistic v-bind:value="10" v-bind:label="user_stories_label" class="baseline-statistic-user-stories"/>
        <statistic v-bind:value="11" v-bind:label="bugs_label" class="baseline-statistic-bugs"/>
        <statistic v-bind:value="33" v-bind:label="tasks_label" class="baseline-statistic-tasks"/>
        <statistic v-bind:value="400" v-bind:label="initial_effort_label" class="baseline-statistic-initial-effort"/>
    </div>
</template>

<script>
import Statistic from "../common/Statistic.vue";

export default {
    name: "BaselineStatistics",
    components: { Statistic },
    computed: {
        epics_label() {
            return this.$gettext("Epics");
        },
        user_stories_label() {
            return this.$gettext("User stories");
        },
        bugs_label() {
            return this.$gettext("Bugs");
        },
        tasks_label() {
            return this.$gettext("Tasks");
        },
        initial_effort_label() {
            return this.$gettext("Initial effort");
        }
    }
};
</script>
