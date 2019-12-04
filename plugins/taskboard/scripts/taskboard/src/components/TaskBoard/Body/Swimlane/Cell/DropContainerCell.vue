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
    <div class="taskboard-cell"
         v-bind:class="classes"
         v-on:mouseenter="mouseEntersCollapsedColumn"
         v-on:mouseout="mouseLeavesCollapsedColumn"
         v-on:click="expandCollapsedColumn"
         data-is-container="true"
         v-bind:data-swimlane-id="swimlane.card.id"
         v-bind:data-column-id="column.id"
         v-bind:data-accepted-trackers-ids="accepted_trackers_ids(column)"
    >
        <slot class="content" v-if="!column.is_collapsed"></slot>
        <cell-disallows-drop-overlay v-bind:is-column-collapsed="column.is_collapsed"/>
    </div>
</template>

<script lang="ts">
import { namespace } from "vuex-class";
import { Component, Mixins, Prop } from "vue-property-decorator";
import HoveringStateForCollapsedColumnMixin from "./hovering-state-for-collapsed-column-mixin";
import ExpandCollapsedColumnMixin from "./expand-collapsed-column-mixin";
import ClassesForCollapsedColumnMixin from "./classes-for-collapsed-column-mixin";
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import { ColumnDefinition, Swimlane } from "../../../../../type";

const column_store = namespace("column");

@Component({
    components: { CellDisallowsDropOverlay }
})
export default class DropContainerCell extends Mixins(
    HoveringStateForCollapsedColumnMixin,
    ExpandCollapsedColumnMixin,
    ClassesForCollapsedColumnMixin
) {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @column_store.Getter
    readonly accepted_trackers_ids!: (column: ColumnDefinition) => number[];
}
</script>
