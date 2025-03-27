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
    <div
        class="taskboard-cell"
        v-bind:class="drop_classes"
        v-on:pointerenter="pointerEntersColumn(column)"
        v-on:pointerleave="pointerLeavesColumn({ column, card_being_dragged })"
        v-on:click="expandColumn(column)"
        data-is-container="true"
        v-bind:data-swimlane-id="swimlane.card.id"
        v-bind:data-column-id="column.id"
        v-bind:data-accepted-trackers-ids="accepted_trackers_ids(column)"
        data-navigation="cell"
    >
        <slot class="content" v-if="!column.is_collapsed"></slot>
        <add-card
            v-if="is_add_card_rendered"
            v-bind:column="column"
            v-bind:swimlane="swimlane"
            v-bind:button_label="add_button_label"
        />
        <cell-disallows-drop-overlay v-bind:is-column-collapsed="column.is_collapsed" />
    </div>
</template>

<script lang="ts">
import { Getter, namespace, State } from "vuex-class";
import { Component, Prop } from "vue-property-decorator";
import AddCard from "../Card/Add/AddCard.vue";
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../type";
import { useClassesForCollapsedColumn } from "./classes-for-collapsed-column-composable";
import type { DraggedCard } from "../../../../../store/type";
import type { PointerLeavesColumnPayload } from "../../../../../store/column/type";
import Vue from "vue";

const column_store = namespace("column");
const swimlane = namespace("swimlane");

@Component({
    components: { AddCard, CellDisallowsDropOverlay },
})
export default class DropContainerCell extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @State
    readonly card_being_dragged!: DraggedCard | null;

    @column_store.Mutation
    readonly pointerEntersColumn!: (column: ColumnDefinition) => void;

    @column_store.Mutation
    readonly pointerLeavesColumn!: (payload: PointerLeavesColumnPayload) => void;

    @column_store.Action
    readonly expandColumn!: (column: ColumnDefinition) => void;

    @column_store.Getter
    readonly accepted_trackers_ids!: (column: ColumnDefinition) => number[];

    @Getter
    readonly can_add_in_place!: (swimlane: Swimlane) => boolean;

    @swimlane.Getter
    readonly is_there_at_least_one_children_to_display!: (current_swimlane: Swimlane) => boolean;

    get is_add_card_rendered(): boolean {
        return this.can_add_in_place(this.swimlane) && !this.column.is_collapsed;
    }

    get add_button_label(): string {
        return !this.is_there_at_least_one_children_to_display(this.swimlane)
            ? this.$gettext("Add child")
            : "";
    }

    get drop_classes(): string[] {
        const classes = useClassesForCollapsedColumn(this.column).getClasses();
        if (this.is_add_card_rendered) {
            classes.push("taskboard-cell-with-add-form");
        }

        return classes;
    }
}
</script>
