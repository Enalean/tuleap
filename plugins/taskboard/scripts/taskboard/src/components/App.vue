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
        <under-construction-modal/>
        <global-app-error v-if="has_global_error"/>
        <board-without-any-columns-error v-else-if="! has_at_least_one_column"/>
        <task-board v-else-if="has_content"/>
        <no-content-empty-state v-else/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace, State } from "vuex-class";
import { Component } from "vue-property-decorator";
import BoardWithoutAnyColumnsError from "./GlobalError/BoardWithoutAnyColumnsError.vue";
import GlobalAppError from "./GlobalError/GlobalAppError.vue";
import UnderConstructionModal from "./UnderConstruction/UnderConstructionModal.vue";
import { ColumnDefinition } from "../type";
import TaskBoard from "./TaskBoard/TaskBoard.vue";
import NoContentEmptyState from "./EmptyState/NoContentEmptyState.vue";

const error = namespace("error");

@Component({
    components: {
        NoContentEmptyState,
        TaskBoard,
        BoardWithoutAnyColumnsError,
        GlobalAppError,
        UnderConstructionModal
    }
})
export default class App extends Vue {
    @State
    readonly columns!: Array<ColumnDefinition>;

    @State
    readonly has_content!: boolean;

    @error.Getter
    readonly has_global_error!: boolean;

    get has_at_least_one_column(): boolean {
        return this.columns.length > 0;
    }
}
</script>
