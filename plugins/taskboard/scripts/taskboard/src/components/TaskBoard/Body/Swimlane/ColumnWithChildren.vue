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
    <drop-container-cell v-bind:column="column" v-bind:swimlane="swimlane">
        <child-card v-for="card of cards" v-bind:key="card.id" v-bind:card="card"/>
        <template v-if="swimlane.is_loading_children_cards">
            <card-skeleton v-for="i in nb_skeletons_to_display" v-bind:key="i"/>
        </template>
        <add-card v-if="is_add_card_rendered" v-bind:column="column" v-bind:swimlane="swimlane" v-bind:parent="swimlane.card"/>
    </drop-container-cell>
</template>

<script lang="ts">
import { Component, Mixins, Prop } from "vue-property-decorator";
import { Card, ColumnDefinition, Swimlane } from "../../../../type";
import { namespace, Getter } from "vuex-class";
import ChildCard from "./Card/ChildCard.vue";
import CardSkeleton from "./Skeleton/CardSkeleton.vue";
import SkeletonMixin from "./Skeleton/skeleton-mixin";
import DropContainerCell from "./Cell/DropContainerCell.vue";
import AddCard from "./Card/Add/AddCard.vue";

const swimlane = namespace("swimlane");

@Component({
    components: { AddCard, DropContainerCell, ChildCard, CardSkeleton }
})
export default class ColumnWithChildren extends Mixins(SkeletonMixin) {
    @Prop({ required: true })
    readonly column!: ColumnDefinition;

    @Prop({ required: true })
    readonly swimlane!: Swimlane;

    @Getter
    readonly can_add_in_place!: (swimlane: Swimlane) => boolean;

    @swimlane.Getter
    readonly cards_in_cell!: (
        current_swimlane: Swimlane,
        current_column: ColumnDefinition
    ) => Card[];

    get cards(): Card[] {
        return this.cards_in_cell(this.swimlane, this.column);
    }

    get nb_skeletons_to_display(): number {
        if (this.cards.length > 0) {
            return 1;
        }

        return this.nb_skeletons;
    }

    get is_add_card_rendered(): boolean {
        return this.can_add_in_place(this.swimlane);
    }
}
</script>
