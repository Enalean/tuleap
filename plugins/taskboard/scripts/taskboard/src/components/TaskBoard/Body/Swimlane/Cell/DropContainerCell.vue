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

<script setup lang="ts">
import { computed } from "vue";
import {
    useGetters,
    useNamespacedActions,
    useNamespacedGetters,
    useNamespacedMutations,
    useStore,
} from "vuex-composition-helpers";
import { useGettext } from "@tuleap/vue2-gettext-composition-helper";
import AddCard from "../Card/Add/AddCard.vue";
import CellDisallowsDropOverlay from "./CellDisallowsDropOverlay.vue";
import type { ColumnDefinition, Swimlane } from "../../../../../type";
import { useClassesForCollapsedColumn } from "./classes-for-collapsed-column-composable";
import type { DraggedCard } from "../../../../../store/type";

const { $gettext } = useGettext();

const props = defineProps<{
    swimlane: Swimlane;
    column: ColumnDefinition;
}>();

const store = useStore();
const card_being_dragged = computed((): DraggedCard | null => store.state.card_being_dragged);

const { pointerEntersColumn, pointerLeavesColumn } = useNamespacedMutations("column", [
    "pointerEntersColumn",
    "pointerLeavesColumn",
]);

const { expandColumn } = useNamespacedActions("column", ["expandColumn"]);

const accepted_trackers_ids = computed(
    (): ((column: ColumnDefinition) => number[]) => store.getters["column/accepted_trackers_ids"],
);

const { can_add_in_place } = useGetters(["can_add_in_place"]);

const { is_there_at_least_one_children_to_display } = useNamespacedGetters("swimlane", [
    "is_there_at_least_one_children_to_display",
]);

const is_add_card_rendered = computed(
    (): boolean => can_add_in_place.value(props.swimlane) && !props.column.is_collapsed,
);

const add_button_label = computed((): string =>
    !is_there_at_least_one_children_to_display.value(props.swimlane) ? $gettext("Add child") : "",
);

const drop_classes = computed((): string[] => {
    const column_classes = useClassesForCollapsedColumn(props.column).getClasses();
    if (!is_add_card_rendered.value) {
        return column_classes;
    }
    return [...column_classes, "taskboard-cell-with-add-form"];
});
</script>
