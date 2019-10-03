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
    <div class="taskboard-head">
        <div class="taskboard-header taskboard-cell-swimlane-header" v-bind:class="fullscreen_class"></div>
        <task-board-header-cell v-for="col of columns" v-bind:key="col.id" v-bind:column="col"/>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { State, namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import { ColumnDefinition } from "../../type";
import TaskBoardHeaderCell from "./TaskBoardHeaderCell.vue";

const fullscreen = namespace("fullscreen");

@Component({
    components: { TaskBoardHeaderCell }
})
export default class TaskBoardHeader extends Vue {
    @State
    readonly columns!: Array<ColumnDefinition>;

    @fullscreen.Getter
    readonly fullscreen_class!: string;
}
</script>
