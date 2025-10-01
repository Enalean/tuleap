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
    <tr v-if="display_void_trackers" data-test="project-timetracking-table-row">
        <td>
            <a v-bind:href="html_url">
                <span>{{ time.label }}</span>
            </a>
        </td>
        <td>
            <a v-bind:href="link_to_project_homepage">{{ time.project.label }}</a>
        </td>
        <td class="tlp-table-cell-numeric">
            {{ project_timetracking_store.get_formatted_time(time) }}
        </td>
    </tr>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { strictInject } from "@tuleap/vue-strict-inject";
import type { TrackerWithTimes } from "@tuleap/plugin-timetracking-rest-api-types";
import { REPORT_ID } from "../injection-symbols";
import { useProjectTimetrackingWidgetStore } from "../store";

const project_timetracking_store = useProjectTimetrackingWidgetStore(strictInject(REPORT_ID))();

const props = defineProps<{
    time: TrackerWithTimes;
}>();

const html_url = computed(() => "/plugins/tracker/?tracker=" + props.time.id);
const link_to_project_homepage = computed(() => "/projects/" + props.time.project.shortname);
const display_void_trackers = computed(() => {
    return !(
        project_timetracking_store.are_void_trackers_hidden &&
        project_timetracking_store.is_tracker_total_sum_equals_zero(props.time.time_per_user)
    );
});
</script>
