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
    <select
        class="tlp-select timetracking-overview-project-selector-input"
        id="project"
        name="project"
        ref="select_project"
        v-on:change="getTrackers()"
        data-test="overview-project-list"
    >
        <option selected v-bind:value="null">{{ $gettext("Please choose...") }}</option>
        <option v-for="project in projects" v-bind:key="project.id" v-bind:value="project.id">
            {{ project.label }}
        </option>
    </select>
</template>

<script>
import { inject } from "vue";
import { useOverviewWidgetStore } from "../../store/index.js";

export default {
    name: "TimeTrackingOverviewProjectOption",
    props: {
        projects: Array,
    },
    setup: () => {
        const overview_store = useOverviewWidgetStore(inject("report_id"))();
        return { overview_store };
    },
    mounted() {
        this.getTrackers();
    },
    methods: {
        getTrackers() {
            let opt = this.$refs.select_project.options;
            if (opt[opt.selectedIndex] && opt[opt.selectedIndex].value) {
                this.overview_store.getTrackers(opt[opt.selectedIndex].value);
            }
        },
    },
};
</script>
