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
        <no-data-to-show-empty-state v-if="should_display_empty_state" />
        <something-went-wrong-empty-state
            v-else-if="should_display_error_state"
            v-bind:message="error_message"
        />
        <loading-state v-else-if="is_loading" />
        <gantt-board v-else v-bind:visible_natures="visible_natures" />
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import NoDataToShowEmptyState from "./NoDataToShowEmptyState.vue";
import SomethingWentWrongEmptyState from "./SomethingWentWrongEmptyState.vue";
import GanttBoard from "./Gantt/GanttBoard.vue";
import type { NaturesLabels } from "../type";
import LoadingState from "./LoadingState.vue";
import { Action, State } from "vuex-class";

@Component({
    components: { LoadingState, GanttBoard, SomethingWentWrongEmptyState, NoDataToShowEmptyState },
})
export default class App extends Vue {
    @Prop({ required: true })
    readonly roadmap_id!: number;

    @Prop({ required: true })
    readonly visible_natures!: NaturesLabels;

    @State
    error_message!: string;

    @State
    should_display_error_state!: boolean;

    @State
    should_display_empty_state!: boolean;

    @Action
    private readonly loadRoadmap!: (roadmap_id: number) => Promise<void>;

    @State
    readonly is_loading!: boolean;

    mounted(): void {
        this.loadRoadmap(this.roadmap_id);
    }
}
</script>
