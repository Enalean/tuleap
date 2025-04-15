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
    <div class="taskboard-body" data-test="taskboard-body">
        <template v-for="swimlane of swimlanes">
            <template v-if="swimlane.card.is_open || are_closed_items_displayed">
                <collapsed-swimlane
                    v-bind:key="swimlane.card.id"
                    v-bind:swimlane="swimlane"
                    v-if="swimlane.card.is_collapsed"
                />
                <children-swimlane
                    v-bind:key="swimlane.card.id"
                    v-bind:swimlane="swimlane"
                    v-else-if="is_there_at_least_one_children_to_display(swimlane)"
                />
                <invalid-mapping-swimlane
                    v-bind:key="swimlane.card.id"
                    v-bind:swimlane="swimlane"
                    v-else-if="hasInvalidMapping(swimlane)"
                />
                <solo-swimlane v-bind:key="swimlane.card.id" v-bind:swimlane="swimlane" v-else />
            </template>
        </template>
        <swimlane-skeleton v-if="is_loading_swimlanes" />
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import type { Swimlane } from "../../../type";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import ChildrenSwimlane from "./Swimlane/ChildrenSwimlane.vue";
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";
import SoloSwimlane from "./Swimlane/SoloSwimlane.vue";
import InvalidMappingSwimlane from "./Swimlane/InvalidMappingSwimlane.vue";
import { getColumnOfCard } from "../../../helpers/list-value-to-column-mapper";
import {
    useNamespacedActions,
    useNamespacedGetters,
    useNamespacedState,
    useState,
    useStore,
} from "vuex-composition-helpers";

const store = useStore();

const { columns } = useNamespacedState("column", ["columns"]);

const { loadSwimlanes } = useNamespacedActions("swimlane", ["loadSwimlanes"]);

const is_loading_swimlanes = computed((): boolean => {
    return store.state.swimlane.is_loading_swimlanes;
});

const swimlanes = computed((): Swimlane[] => {
    return store.state.swimlane.swimlanes;
});

const { are_closed_items_displayed } = useState(["are_closed_items_displayed"]);

const { is_there_at_least_one_children_to_display } = useNamespacedGetters("swimlane", [
    "is_there_at_least_one_children_to_display",
]);

onMounted(() => {
    loadSwimlanes();
});

function hasInvalidMapping(swimlane: Swimlane): boolean {
    return getColumnOfCard(columns.value, swimlane.card) === undefined;
}
</script>
