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
    <div class="taskboard-swimlane">
        <parent-cell v-bind:card="swimlane.card"/>
        <template v-for="(col, index) of columns">
            <columns-skeleton
                v-if="swimlane.is_loading_children_cards"
                v-bind:key="col.id"
                v-bind:column_index="index"
            />
            <div class="taskboard-cell" v-else v-bind:key="col.id">
                <template v-for="card of getCardsOfColumn(col)">
                    <child-card v-bind:key="card.id" v-bind:card="card"/>
                </template>
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition, Swimlane } from "../../../type";
import { State } from "vuex-class";
import ParentCell from "./ParentCell.vue";
import ColumnsSkeleton from "../ColumnsSkeleton.vue";
import ChildCard from "../Card/ChildCard.vue";
import { getColumnOfCard } from "../../../helpers/list-value-to-column-mapper";

@Component({
    components: { ChildCard, ColumnsSkeleton, ParentCell }
})
export default class CardWithChildren extends Vue {
    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @State
    readonly columns!: Array<ColumnDefinition>;

    getCardsOfColumn(column: ColumnDefinition): Card[] {
        return this.swimlane.children_cards.filter(card => this.getColumnOfCard(card) === column);
    }

    getColumnOfCard(card: Card): ColumnDefinition | undefined {
        return getColumnOfCard(this.columns, card);
    }
}
</script>
