<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div
        data-test="move-modal-selectors"
        v-bind:class="{
            'move-artifact-selectors': true,
            'move-artifact-selectors-preview': has_processed_dry_run,
        }"
    >
        <project-selector v-if="!is_loading_initial" />
        <div
            data-test="move-modal-selectors-spinner"
            v-bind:class="{
                'move-artifact-tracker-loader': true,
                'move-artifact-tracker-loader-spinner': are_trackers_loading,
            }"
        ></div>
        <tracker-selector v-if="!is_loading_initial" />
    </div>
</template>

<script>
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";
import { mapState } from "vuex";

export default {
    name: "MoveModalSelectors",
    components: {
        ProjectSelector,
        TrackerSelector,
    },
    computed: {
        ...mapState(["is_loading_initial", "are_trackers_loading", "has_processed_dry_run"]),
    },
};
</script>
