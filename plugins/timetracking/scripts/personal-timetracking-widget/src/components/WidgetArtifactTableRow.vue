<!--
  - Copyright Enalean (c) 2018 - Present. All rights reserved.
  -
  - Tuleap and Enalean names and logos are registrated trademarks owned by
  - Enalean SAS. All other trademarks or names are properties of their respective
  - owners.
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
        <td class="timetracking-widget-artifact-cell">
            <widget-link-to-artifact v-bind:artifact="artifact" />
        </td>
        <td>
            <a v-bind:href="/projects/ + project.shortname">{{ project.label }}</a>
        </td>
        <td class="tlp-table-cell-numeric">
            {{ personal_store.get_formatted_aggregated_time(time_data) }}
        </td>

        <widget-modal-times
            v-bind:artifact="artifact"
            v-bind:project="project"
            v-bind:times="time_data"
        />
    </tr>
</template>
<script setup lang="ts">
import { ref } from "vue";
import type { PersonalTime } from "@tuleap/plugin-timetracking-rest-api-types";
import { usePersonalTimetrackingWidgetStore } from "../store/root";
import WidgetModalTimes from "./modal/WidgetModalTimes.vue";
import WidgetLinkToArtifact from "./WidgetLinkToArtifact.vue";

const props = defineProps<{
    time_data: PersonalTime[];
}>();

const personal_store = usePersonalTimetrackingWidgetStore();
const artifact = ref(props.time_data[0].artifact);
const project = ref(props.time_data[0].project);
</script>

<style scoped lang="scss">
.timetracking-widget-artifact-cell {
    max-width: 400px;
}
</style>
