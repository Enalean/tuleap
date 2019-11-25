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
         v-bind:data-swimlane-id="swimlane.card.id"
         v-bind:data-column-id="column.id"
         v-bind:data-accepted-trackers-ids="accepted_trackers_ids(column)"
         v-on:mouseenter="mouseEntersCollapsedColumn"
         v-on:mouseout="mouseLeavesCollapsedColumn"
         v-on:click="expandCollapsedColumn"
    >
        <template v-if="!column.is_collapsed">
            <template v-for="card of cards">
                <child-card v-bind:key="card.id" v-bind:card="card"/>
            </template>
            <template v-if="swimlane.is_loading_children_cards">
                <card-skeleton v-for="i in nb_skeletons_to_display" v-bind:key="i"/>
            </template>
        </template>
        <cell-disallows-drop-overlay v-bind:is-drop-rejected="does_cell_reject_drop(swimlane, column)"/>
    </div>
</template>

<script lang="ts">
import { Component, Mixins, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition, Swimlane } from "../../../../type";
import { namespace } from "vuex-class";
import ChildCard from "./Card/ChildCard.vue";
import CardSkeleton from "./Skeleton/CardSkeleton.vue";
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import SkeletonMixin from "./Skeleton/skeleton-mixin";
import HoveringStateForCollapsedColumnMixin from "./hovering-state-for-collapsed-column-mixin";
import ExpandCollapsedColumnMixin from "./expand-collapsed-column-mixin";
import ClassesForCollapsedColumnMixin from "./classes-for-collapsed-column-mixin";
import ClassesForDropZonesMixin from "./classes-for-drop-zones-mixin";

const swimlane = namespace("swimlane");
const column = namespace("column");

@Component({
    components: { ChildCard, CardSkeleton, CellDisallowsDropOverlay }
})
export default class ColumnWithChildren extends Mixins(
    SkeletonMixin,
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
    readonly cards_in_cell!: (
        current_swimlane: Swimlane,
        current_column: ColumnDefinition
    ) => Card[];

    @column.Getter
    readonly accepted_trackers_ids!: (column: ColumnDefinition) => number[];

    @swimlane.Getter
    readonly does_cell_reject_drop!: (swimlane: Swimlane, column: ColumnDefinition) => boolean;

    get cards(): Card[] {
        return this.cards_in_cell(this.swimlane, this.column);
    }

    get nb_skeletons_to_display(): number {
        if (this.cards.length > 0) {
            return 1;
        }

        return this.nb_skeletons;
    }
}
</script>
