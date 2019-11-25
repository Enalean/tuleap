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
         v-bind:class="[classes, dropzone_classes]"
         v-on:mouseenter="mouseEntersCollapsedColumn"
         v-on:mouseout="mouseLeavesCollapsedColumn"
         v-on:click="expandCollapsedColumn"
    >
        <slot v-if="!column.is_collapsed"></slot>
        <cell-disallows-drop-overlay v-bind:is-drop-rejected="does_cell_reject_drop(swimlane, column)"/>
    </div>
</template>

<script lang="ts">
import { Component, Mixins, Prop } from "vue-property-decorator";
import { ColumnDefinition, Swimlane } from "../../../../type";
import { namespace } from "vuex-class";
import HoveringStateForCollapsedColumnMixin from "./hovering-state-for-collapsed-column-mixin";
import ExpandCollapsedColumnMixin from "./expand-collapsed-column-mixin";
import ClassesForCollapsedColumnMixin from "./classes-for-collapsed-column-mixin";
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import ClassesForDropZonesMixin from "./classes-for-drop-zones-mixin";

const swimlane = namespace("swimlane");

@Component({
    components: { CellDisallowsDropOverlay }
})
export default class CellForSoloCard extends Mixins(
    HoveringStateForCollapsedColumnMixin,
    ExpandCollapsedColumnMixin,
    ClassesForCollapsedColumnMixin,
    ClassesForDropZonesMixin
) {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @swimlane.Getter
    readonly does_cell_reject_drop!: (swimlane: Swimlane, column: ColumnDefinition) => boolean;
}
</script>
