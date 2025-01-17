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
        <child-card v-for="card of cards" v-bind:key="card.id" v-bind:card="card" />
        <template v-if="swimlane.is_loading_children_cards">
            <card-skeleton v-for="i in nb_skeletons_to_display" v-bind:key="i" />
        </template>
    </drop-container-cell>
</template>

<script setup lang="ts">
import type { Card, ColumnDefinition, Swimlane } from "../../../../../type";
import ChildCard from "../Card/ChildCard.vue";
import CardSkeleton from "../Skeleton/CardSkeleton.vue";
import DropContainerCell from "./DropContainerCell.vue";
import { useSkeletons } from "../Skeleton/skeleton-composable";
import { useNamespacedGetters } from "vuex-composition-helpers";
import { computed } from "vue";

const props = defineProps<{
    column: ColumnDefinition;
    swimlane: Swimlane;
}>();

const { cards_in_cell } = useNamespacedGetters("swimlane", ["cards_in_cell"]);

const cards = computed((): Card[] => {
    return cards_in_cell.value(props.swimlane, props.column);
});

const nb_skeletons_to_display = computed((): number => {
    if (cards.value.length > 0) {
        return 1;
    }

    return useSkeletons(cards.value.length);
});
</script>
