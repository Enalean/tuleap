<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
  -->

<template>
    <div
        class="taskboard-cell"
        v-bind:class="classes"
        v-on:pointerenter="pointerEntersColumnWithCheck(column)"
        v-on:pointerleave="pointerLeavesColumnWithCheck({ column, card_being_dragged })"
        v-on:click="expandColumn(column)"
        data-navigation="cell"
    >
        <add-card v-if="is_add_card_rendered" v-bind:column="column" v-bind:swimlane="swimlane" />
    </div>
</template>

<script lang="ts">
import { Component, Prop } from "vue-property-decorator";
import { Getter, namespace, State } from "vuex-class";
import AddCard from "../Card/Add/AddCard.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../type";
import { useClassesForCollapsedColumn } from "./classes-for-collapsed-column-composable";
import type { DraggedCard } from "../../../../../store/type";
import type { PointerLeavesColumnPayload } from "../../../../../store/column/type";
import Vue from "vue";

const column_store = namespace("column");

@Component({
    components: { AddCard },
})
export default class InvalidMappingCell extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @State
    readonly card_being_dragged!: DraggedCard | null;

    @column_store.Mutation
    readonly pointerEntersColumnWithCheck!: (column: ColumnDefinition) => void;

    @column_store.Mutation
    readonly pointerLeavesColumnWithCheck!: (payload: PointerLeavesColumnPayload) => void;

    @Getter
    readonly can_add_in_place!: (swimlane: Swimlane) => boolean;

    @column_store.Action
    readonly expandColumn!: (column: ColumnDefinition) => void;

    get is_add_card_rendered(): boolean {
        return this.can_add_in_place(this.swimlane);
    }

    get classes(): string[] {
        return useClassesForCollapsedColumn(this.column).getClasses();
    }
}
</script>
