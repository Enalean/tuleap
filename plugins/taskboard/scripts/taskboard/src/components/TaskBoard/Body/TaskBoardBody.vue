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
                    v-else-if="swimlane.card.has_children"
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

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace, State } from "vuex-class";
import { ColumnDefinition, Swimlane } from "../../../type";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import ChildrenSwimlane from "./Swimlane/ChildrenSwimlane.vue";
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";
import SoloSwimlane from "./Swimlane/SoloSwimlane.vue";
import InvalidMappingSwimlane from "./Swimlane/InvalidMappingSwimlane.vue";
import { getColumnOfCard } from "../../../helpers/list-value-to-column-mapper";

const column = namespace("column");
const swimlane = namespace("swimlane");

@Component({
    components: {
        ChildrenSwimlane,
        InvalidMappingSwimlane,
        SoloSwimlane,
        SwimlaneSkeleton,
        CollapsedSwimlane,
    },
})
export default class TaskBoardBody extends Vue {
    @State
    readonly are_closed_items_displayed!: boolean;

    @column.State
    readonly columns!: Array<ColumnDefinition>;

    @swimlane.State
    readonly swimlanes!: Array<Swimlane>;

    @swimlane.State
    readonly is_loading_swimlanes!: boolean;

    @swimlane.Action
    loadSwimlanes!: () => void;

    created(): void {
        this.loadSwimlanes();
    }

    hasInvalidMapping(swimlane: Swimlane): boolean {
        return getColumnOfCard(this.columns, swimlane.card) === undefined;
    }
}
</script>
