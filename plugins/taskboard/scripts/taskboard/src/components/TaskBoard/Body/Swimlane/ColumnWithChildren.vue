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
    <div class="taskboard-cell" v-bind:class="classes">
        <template v-if="!column.is_collapsed">
            <template v-for="card of cards">
                <child-card v-bind:key="card.id" v-bind:card="card"/>
            </template>
            <template v-if="swimlane.is_loading_children_cards">
                <card-skeleton v-for="i in nb_skeletons_to_display" v-bind:key="i"/>
            </template>
        </template>
    </div>
</template>

<script lang="ts">
import { Component, Mixins, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition, Swimlane } from "../../../../type";
import { State } from "vuex-class";
import ChildCard from "./Card/ChildCard.vue";
import CardSkeleton from "./Skeleton/CardSkeleton.vue";
import { getColumnOfCard } from "../../../../helpers/list-value-to-column-mapper";
import SkeletonMixin from "./Skeleton/skeleton-mixin";

@Component({
    components: { ChildCard, CardSkeleton }
})
export default class ColumnWithChildren extends Mixins(SkeletonMixin) {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @State
    readonly columns!: Array<ColumnDefinition>;

    get cards(): Card[] {
        return this.swimlane.children_cards.filter(card => {
            const column_of_card = this.getColumnOfCard(card);
            if (!column_of_card) {
                return false;
            }

            return column_of_card.id === this.column.id;
        });
    }

    getColumnOfCard(card: Card): ColumnDefinition | undefined {
        return getColumnOfCard(this.columns, card);
    }

    get nb_skeletons_to_display(): number {
        if (this.cards.length > 0) {
            return 1;
        }

        return this.nb_skeletons;
    }

    get classes(): string {
        return this.column.is_collapsed ? "taskboard-cell-collapsed" : "";
    }
}
</script>
