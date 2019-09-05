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
    <div>
        <under-construction-modal/>
        <task-board v-bind:columns="columns" v-if="has_at_least_one_column"/>
        <board-without-any-columns-error v-else/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import BoardWithoutAnyColumnsError from "./GlobalError/BoardWithoutAnyColumnsError.vue";
import UnderConstructionModal from "./UnderConstruction/UnderConstructionModal.vue";
import { ColumnDefinition } from "../type";
import TaskBoard from "./TaskBoard/TaskBoard.vue";

@Component({
    components: { TaskBoard, BoardWithoutAnyColumnsError, UnderConstructionModal }
})
export default class App extends Vue {
    @Prop()
    readonly columns!: Array<ColumnDefinition>;

    get has_at_least_one_column(): boolean {
        return this.columns.length > 0;
    }
}
</script>
