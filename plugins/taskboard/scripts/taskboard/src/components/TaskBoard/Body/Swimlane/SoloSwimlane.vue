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
        class="taskboard-swimlane"
        data-navigation="swimlane"
        tabindex="0"
        v-if="should_solo_card_be_displayed"
    >
        <swimlane-header v-bind:swimlane="swimlane" />
        <drop-container-cell
            v-for="col of columns"
            v-bind:key="col.id"
            v-bind:column="col"
            v-bind:swimlane="swimlane"
            v-bind:is_solo_card="true"
        />
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { ColumnDefinition, Swimlane } from "../../../../type";
import SwimlaneHeader from "./Header/SwimlaneHeader.vue";
import { getColumnOfCard } from "../../../../helpers/list-value-to-column-mapper";
import { useStore } from "vuex-composition-helpers";
import DropContainerCell from "./Cell/DropContainerCell.vue";

const props = defineProps<{
    swimlane: Swimlane;
}>();

const store = useStore();

const columns = computed((): ColumnDefinition[] => {
    return store.state.column.columns;
});

function getColumnOfSoloCard(swimlane: Swimlane): ColumnDefinition {
    const column = getColumnOfCard(columns.value, swimlane.card);
    if (column === undefined) {
        throw new Error("Solo card must have a mapping");
    }
    return column;
}
const column = computed((): ColumnDefinition => {
    return getColumnOfSoloCard(props.swimlane);
});

const should_solo_card_be_displayed = computed((): boolean => {
    return !column.value.is_collapsed;
});
</script>
