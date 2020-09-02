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
    <div class="switch-to-recent-items">
        <h2 class="tlp-modal-subtitle switch-to-modal-body-title" v-translate>Recent items</h2>
        <recent-items-loading-state v-if="is_loading_history" />
        <recent-items-empty-state v-if="has_no_history" />
        <template v-if="has_history">
            <recent-items-entry
                v-for="entry of history.entries"
                v-bind:key="entry.html_url"
                v-bind:entry="entry"
            />
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import RecentItemsEmptyState from "./RecentItemsEmptyState.vue";
import RecentItemsLoadingState from "./RecentItemsLoadingState.vue";
import RecentItemsEntry from "./RecentItemsEntry.vue";
import { State } from "vuex-class";
import { UserHistory } from "../../type";

@Component({
    components: { RecentItemsEmptyState, RecentItemsLoadingState, RecentItemsEntry },
})
export default class ListOfRecentItems extends Vue {
    @State
    readonly is_loading_history!: boolean;

    @State
    readonly is_history_loaded!: boolean;

    @State
    readonly history!: UserHistory;

    get has_no_history(): boolean {
        if (!this.is_history_loaded) {
            return false;
        }

        return this.history.entries.length === 0;
    }

    get has_history(): boolean {
        if (!this.is_history_loaded) {
            return false;
        }

        return this.history.entries.length > 0;
    }
}
</script>
