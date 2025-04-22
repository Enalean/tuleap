<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-app">
        <global-app-error v-if="has_global_error" />
        <board-without-any-columns-error v-else-if="!has_at_least_one_column" />
        <task-board v-else-if="has_content" />
        <no-content-empty-state v-else />
    </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted } from "vue";
import { type ColumnDefinition, TaskboardEvent } from "../type";
import TaskBoard from "./TaskBoard/TaskBoard.vue";
import EventBus from "./../helpers/event-bus";
import NoContentEmptyState from "./EmptyState/NoContentEmptyState.vue";
import BoardWithoutAnyColumnsError from "./GlobalError/BoardWithoutAnyColumnsError.vue";
import GlobalAppError from "./GlobalError/GlobalAppError.vue";
import {
    useGetters,
    useNamespacedGetters,
    useNamespacedState,
    useState,
    useStore,
} from "vuex-composition-helpers";
import type { ErrorState } from "../store/error/type";

const store = useStore();

const columns = computed((): ColumnDefinition[] => {
    return store.state.column.columns;
});

const { has_global_error } = useNamespacedState<Pick<ErrorState, "has_global_error">>("error", [
    "has_global_error",
]);

const { has_at_least_one_card_in_edit_mode } = useNamespacedGetters("swimlane", [
    "has_at_least_one_card_in_edit_mode",
]);

const { has_content } = useState(["has_content"]);
const { has_at_least_one_cell_in_add_mode } = useGetters(["has_at_least_one_cell_in_add_mode"]);

const has_at_least_one_column = computed((): boolean => {
    return columns.value.length > 0;
});

onMounted(() => {
    window.addEventListener("beforeunload", beforeUnload);
    document.addEventListener("keyup", keyup);
});

onBeforeUnmount(() => {
    window.removeEventListener("beforeunload", beforeUnload);
    document.removeEventListener("keyup", keyup);
});

function beforeUnload(event: Event): void {
    if (has_at_least_one_card_in_edit_mode.value || has_at_least_one_cell_in_add_mode.value) {
        event.preventDefault();
        event.returnValue = false;
    }
}

function keyup(event: KeyboardEvent): void {
    if (event.key === "Escape") {
        EventBus.$emit(TaskboardEvent.ESC_KEY_PRESSED);
    }
}
</script>
