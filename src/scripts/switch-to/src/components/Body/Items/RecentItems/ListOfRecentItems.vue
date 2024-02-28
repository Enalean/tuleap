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
    <div
        class="switch-to-recent-items"
        data-test="switch-to-recent-items"
        v-if="should_be_displayed"
    >
        <template v-if="has_history">
            <h2
                class="tlp-modal-subtitle switch-to-modal-body-title"
                id="switch-to-modal-recent-items-title"
                v-translate
            >
                Recent items
            </h2>
            <nav
                class="switch-to-recent-items-list"
                aria-labelledby="switch-to-modal-recent-items-title"
                v-if="has_filtered_history"
                data-test="switch-to-recent-items"
            >
                <item-entry
                    v-for="entry of filtered_history.entries"
                    v-bind:key="entry.html_url"
                    v-bind:entry="entry"
                    v-bind:change-focus-callback="changeFocus"
                    v-bind:location="location"
                />
            </nav>
            <p class="switch-to-modal-no-matching-history" v-else>
                <translate>You didn't visit recently any items matching your query.</translate>
            </p>
        </template>
        <recent-items-error-state v-if="is_history_in_error" />
        <recent-items-loading-state v-else-if="is_loading_history" />
        <recent-items-empty-state v-else-if="has_no_history" />
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import RecentItemsEmptyState from "./RecentItemsEmptyState.vue";
import RecentItemsLoadingState from "./RecentItemsLoadingState.vue";
import ItemEntry from "../ItemEntry.vue";
import RecentItemsErrorState from "./RecentItemsErrorState.vue";
import { useRootStore } from "../../../../stores/root";
import type { FocusFromItemPayload } from "../../../../stores/type";
import { storeToRefs } from "pinia";
import { useKeyboardNavigationStore } from "../../../../stores/keyboard-navigation";

const store = useRootStore();

const {
    is_loading_history,
    is_history_loaded,
    is_history_in_error,
    history,
    filtered_history,
    is_in_search_mode,
} = storeToRefs(store);

function changeFocus(payload: FocusFromItemPayload): void {
    useKeyboardNavigationStore().changeFocusFromHistory(payload);
}

const has_no_history = computed((): boolean => {
    if (!is_history_loaded.value) {
        return false;
    }

    return history.value.entries.length === 0;
});

const has_history = computed((): boolean => {
    if (is_history_in_error.value) {
        return false;
    }

    if (!is_history_loaded.value) {
        return false;
    }

    return history.value.entries.length > 0;
});

const has_filtered_history = computed((): boolean => {
    if (!is_history_loaded.value) {
        return false;
    }

    return filtered_history.value.entries.length > 0;
});

const should_be_displayed = computed((): boolean => {
    return is_in_search_mode.value === false || has_filtered_history.value;
});

const location = ref(window.location);
</script>
