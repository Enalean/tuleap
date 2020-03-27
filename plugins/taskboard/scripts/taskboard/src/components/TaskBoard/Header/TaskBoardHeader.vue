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
        <div
            class="taskboard-header taskboard-cell-swimlane-header"
            v-bind:class="taskboard_cell_swimlane_header_classes"
        ></div>
        <template v-for="column of columns">
            <collapsed-header-cell
                v-bind:key="column.id"
                v-if="column.is_collapsed"
                v-bind:column="column"
            />
            <expanded-header-cell v-bind:key="column.id" v-else v-bind:column="column" />
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { namespace } from "vuex-class";
import { Component } from "vue-property-decorator";
import { ColumnDefinition } from "../../../type";
import ExpandedHeaderCell from "./Expanded/ExpandedHeaderCell.vue";
import CollapsedHeaderCell from "./Collapsed/CollapsedHeaderCell.vue";

const column = namespace("column");
const swimlane = namespace("swimlane");

@Component({
    components: { CollapsedHeaderCell, ExpandedHeaderCell },
})
export default class TaskBoardHeader extends Vue {
    @column.State
    readonly columns!: Array<ColumnDefinition>;

    @swimlane.Getter
    readonly taskboard_cell_swimlane_header_classes!: string[];
}
</script>
