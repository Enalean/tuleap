<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
        <no-data-to-show-empty-state
            v-if="should_display_empty_state"
            v-bind:should_invite_to_come_back="true"
        />
        <something-went-wrong-empty-state
            v-else-if="should_display_error_state"
            v-bind:message="error_message"
        />
        <loading-state v-else-if="is_loading" />
        <gantt-board v-else v-bind:visible_natures="visible_natures" />
    </div>
</template>

<script setup lang="ts">
import { onMounted } from "vue";
import type { NaturesLabels } from "../type";
import SomethingWentWrongEmptyState from "./SomethingWentWrongEmptyState.vue";
import GanttBoard from "./Gantt/GanttBoard.vue";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";
import LoadingState from "./LoadingState.vue";
import { useActions, useState } from "vuex-composition-helpers";

const props = defineProps<{
    roadmap_id: number;
    visible_natures: NaturesLabels;
}>();

const { loadRoadmap } = useActions(["loadRoadmap"]);
const { should_display_empty_state, should_display_error_state, is_loading, error_message } =
    useState([
        "should_display_empty_state",
        "should_display_error_state",
        "is_loading",
        "error_message",
    ]);

onMounted(() => {
    loadRoadmap(props.roadmap_id);
});
</script>
