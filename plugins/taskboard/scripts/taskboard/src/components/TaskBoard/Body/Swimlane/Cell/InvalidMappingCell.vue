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
        v-on:pointerenter="pointerEntersColumn(column)"
        v-on:pointerleave="pointerLeavesColumn({ column, card_being_dragged })"
        v-on:click="expandColumn(column)"
        data-navigation="cell"
    >
        <add-card v-if="is_add_card_rendered" v-bind:column="column" v-bind:swimlane="swimlane" />
    </div>
</template>
<script setup lang="ts">
import { computed } from "vue";
import {
    useGetters,
    useNamespacedActions,
    useNamespacedMutations,
    useStore,
} from "vuex-composition-helpers";
import AddCard from "../Card/Add/AddCard.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../type";
import { useClassesForCollapsedColumn } from "./classes-for-collapsed-column-composable";
import type { DraggedCard } from "../../../../../store/type";

const props = defineProps<{ swimlane: Swimlane; column: ColumnDefinition }>();

const store = useStore();
const card_being_dragged = computed((): DraggedCard | null => store.state.card_being_dragged);

const { pointerEntersColumn, pointerLeavesColumn } = useNamespacedMutations("column", [
    "pointerEntersColumn",
    "pointerLeavesColumn",
]);

const { can_add_in_place } = useGetters(["can_add_in_place"]);

const { expandColumn } = useNamespacedActions("column", ["expandColumn"]);

const is_add_card_rendered = computed((): boolean => can_add_in_place.value(props.swimlane));

const classes = computed((): string[] => {
    const column_classes = useClassesForCollapsedColumn(props.column).getClasses();
    if (!is_add_card_rendered.value) {
        return column_classes;
    }
    return [...column_classes, "taskboard-cell-with-add-form"];
});
</script>
