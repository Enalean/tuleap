<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="switch-to-modal-body">
        <global-loading-state v-if="should_global_loading_state_be_displayed" />
        <global-empty-state v-else-if="should_global_empty_state_be_displayed" />
        <template v-else>
            <list-of-projects />
            <list-of-recent-items />
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import ListOfProjects from "./Projects/ListOfProjects.vue";
import ListOfRecentItems from "./RecentItems/ListOfRecentItems.vue";
import GlobalEmptyState from "./GlobalEmptyState.vue";
import { State } from "vuex-class";
import { Project, UserHistory } from "../../type";
import GlobalLoadingState from "./GlobalLoadingState.vue";

@Component({
    components: { GlobalLoadingState, GlobalEmptyState, ListOfProjects, ListOfRecentItems },
})
export default class SwitchToBody extends Vue {
    @State
    readonly is_loading_history!: boolean;

    @State
    private readonly is_history_loaded!: boolean;

    @State
    private readonly history!: UserHistory;

    @State
    private readonly projects!: Project[];

    get should_global_empty_state_be_displayed(): boolean {
        if (!this.is_history_loaded) {
            return false;
        }

        if (this.projects.length > 0) {
            return false;
        }

        return this.history.entries.length === 0;
    }

    get should_global_loading_state_be_displayed(): boolean {
        if (this.projects.length > 0) {
            return false;
        }

        return this.is_loading_history;
    }
}
</script>
