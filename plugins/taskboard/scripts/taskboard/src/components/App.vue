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

<script lang="ts">
import Vue from "vue";
import { namespace, State, Getter } from "vuex-class";
import { Component } from "vue-property-decorator";
import BoardWithoutAnyColumnsError from "./GlobalError/BoardWithoutAnyColumnsError.vue";
import GlobalAppError from "./GlobalError/GlobalAppError.vue";
import { ColumnDefinition, TaskboardEvent } from "../type";
import TaskBoard from "./TaskBoard/TaskBoard.vue";
import NoContentEmptyState from "./EmptyState/NoContentEmptyState.vue";
import EventBus from "./../helpers/event-bus";

const column = namespace("column");
const error = namespace("error");
const swimlane = namespace("swimlane");

@Component({
    components: {
        NoContentEmptyState,
        TaskBoard,
        BoardWithoutAnyColumnsError,
        GlobalAppError,
    },
})
export default class App extends Vue {
    @column.State
    readonly columns!: Array<ColumnDefinition>;

    @State
    readonly has_content!: boolean;

    @error.State
    readonly has_global_error!: boolean;

    @swimlane.Getter
    readonly has_at_least_one_card_in_edit_mode!: boolean;

    @Getter
    readonly has_at_least_one_cell_in_add_mode!: boolean;

    get has_at_least_one_column(): boolean {
        return this.columns.length > 0;
    }

    mounted(): void {
        window.addEventListener("beforeunload", this.beforeUnload);
        document.addEventListener("keyup", this.keyup);
    }

    beforeDestroy(): void {
        window.removeEventListener("beforeunload", this.beforeUnload);
        document.removeEventListener("keyup", this.keyup);
    }

    beforeUnload(event: Event): void {
        if (this.has_at_least_one_card_in_edit_mode || this.has_at_least_one_cell_in_add_mode) {
            event.preventDefault();
            event.returnValue = false;
        } else {
            delete event.returnValue;
        }
    }

    keyup(event: KeyboardEvent): void {
        if (event.key === "Escape") {
            EventBus.$emit(TaskboardEvent.ESC_KEY_PRESSED);
        }
    }
}
</script>
